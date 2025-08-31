<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Complemento;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ComplementosHistorialController extends Controller
{
    public function index(Request $r)
{
    $items = \App\Models\Complemento::with('rfcUsuario:id,rfc,razon_social')
        ->forActiveRfc()                             // scope del modelo (ok)
        ->withSum('pagos', 'monto_pago')             // <-- ¡OJO, con "->"!
        ->when($r->filled('estatus'), fn($q) => $q->where('estatus', $r->estatus))
        ->when($r->filled('buscar'), function ($q) use ($r) {
            $t = '%'.$r->buscar.'%';
            $q->where(function ($w) use ($t) {
                $w->where('uuid', 'like', $t)
                  ->orWhere('razon_social', 'like', $t)
                  ->orWhere('rfc', 'like', $t);
            });
        })
        ->orderByDesc('fecha')
        ->paginate(20)
        ->withQueryString();

    // Email sugerido por RFC (igual que en facturas)
    $emailsPorRfc = \App\Models\Cliente::query()
        ->where('users_id', auth()->id())
        ->whereIn('rfc', $items->pluck('rfc')->filter()->unique())
        ->pluck('email', 'rfc');

    return view('facturacion.complementos.index', compact('items', 'emailsPorRfc'));
}


    public function show(Complemento $complemento)
    {
        $this->authorizeRfc($complemento);

        // Datos para la vista: pagos detallados. Preferimos tabla; si no, parseamos XML.
        $pagos = $complemento->pagos()->with(['factura'])->orderBy('fecha_pago')->get();

        if ($pagos->isEmpty()) {
            // Parsear XML de pagos 2.0/1.0
            $parsed = $this->parsePagosXml($complemento);
        } else {
            $parsed = [
                'pagos' => $pagos->map(function($p){
                    return [
                        'fecha_pago'     => optional($p->fecha_pago)->format('Y-m-d H:i'),
                        'parcialidad'    => $p->parcialidad,
                        'saldo_anterior' => (float)$p->saldo_anterior,
                        'monto_pago'     => (float)$p->monto_pago,
                        'saldo_insoluto' => (float)$p->saldo_insoluto,
                        'documento'      => optional($p->factura)->serie_folio ?? optional($p->factura)->uuid ?? '—',
                    ];
                })->toArray(),
                'totales' => [
                    'monto' => (float)$pagos->sum('monto_pago'),
                ],
            ];
        }

        return view('facturacion.complementos.show', [
            'complemento' => $complemento,
            'parsed' => $parsed,
        ]);
    }

    public function descargarPdf(Complemento $complemento)
    {
        $this->authorizeRfc($complemento);
        $data = $this->decodeB64($complemento->pdf);
        abort_unless($data, 404, 'PDF no disponible');

        $nombre = $this->nombreArchivo($complemento, 'pdf');
        return response()->streamDownload(function() use ($data){
            echo $data;
        }, $nombre, ['Content-Type' => 'application/pdf']);
    }

    public function descargarXml(Complemento $complemento)
    {
        $this->authorizeRfc($complemento);
        // XML TEXTO PLANO (con soporte a base64/data URI)
        $xml = $complemento->xmlRaw();
        abort_unless($xml, 404, 'XML no disponible');

        $nombre = $this->nombreArchivo($complemento, 'xml');
        return response()->streamDownload(function() use ($xml){
            echo $xml;
        }, $nombre, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function enviarEmail(Request $r, Complemento $complemento)
    {
        $this->authorizeRfc($complemento);
        $r->validate([
            'to' => ['required','email'],
            'cc' => ['nullable','email'],
        ]);

        $pdf = $this->decodeB64($complemento->pdf);
        $xml = $complemento->xmlRaw();

        Mail::send('emails.cfdi_plain', ['factura' => $complemento], function($m) use ($r, $complemento, $pdf, $xml) {
            $m->to($r->input('to'));
            if ($r->filled('cc')) $m->cc($r->input('cc'));
            $m->subject('Complemento de pago '.$this->folioLike($complemento));
            if ($pdf) $m->attachData($pdf, $this->nombreArchivo($complemento,'pdf'), ['mime' => 'application/pdf']);
            if ($xml) $m->attachData($xml, $this->nombreArchivo($complemento,'xml'), ['mime' => 'application/xml; charset=UTF-8']);
        });

        return back()->with('ok', 'Correo enviado.');
    }

    // -------- Helpers compartidos --------

    protected function decodeB64(?string $b64): ?string
    {
        if (!$b64) return null;
        if (str_contains($b64, 'base64,')) {
            $b64 = substr($b64, strpos($b64, 'base64,') + 7);
        }
        $raw = base64_decode($b64, true);
        return $raw === false ? null : $raw;
    }

    protected function nombreArchivo($reg, string $ext): string
    {
        $base = $reg->uuid ?: ('Complemento_Pagos_'.optional($reg->fecha)->format('Ymd_His'));
        return trim($base ?: 'complemento').'.'.$ext;
    }

    protected function folioLike($reg): string
    {
        return $reg->uuid ?: ($reg->razon_social.' '.optional($reg->fecha)->format('Y-m-d'));
    }

    protected function authorizeRfc(Complemento $c): void
    {
        abort_unless(optional($c->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);
    }

    /** Parseo robusto de pagos (CFDI 4.0 pago20 y 3.3 pago10) */
    protected function parsePagosXml(Complemento $c): array
    {
        $out = ['pagos' => [], 'totales' => ['monto' => 0.0]];
        $doc = $c->cfdiRoot();
        if (!$doc) return $out;

        $ns = $doc->getNamespaces(true);
        $doc->registerXPathNamespace('cfdi', $ns['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4');

        $pagoNs = $ns['pago20'] ?? $ns['pago'] ?? 'http://www.sat.gob.mx/Pagos20';
        $doc->registerXPathNamespace('p', $pagoNs);

        $A = function($el, $name) { return (string)($el[$name] ?? ''); };

        $pagos = $doc->xpath('//cfdi:Complemento/p:Pagos/p:Pago') ?: [];
        $sum = 0.0;

        foreach ($pagos as $p) {
            $fechaPago = $A($p, 'FechaPago') ?: null;
            $monto     = (float)str_replace(',', '', $A($p, 'Monto') ?: '0');
            $docs = $p->xpath('./p:DoctoRelacionado') ?: [];

            if ($docs) {
                foreach ($docs as $d) {
                    $parcialidad = (int)($A($d, 'NumParcialidad') ?: 0);
                    $saldoAnt    = (float)str_replace(',', '', $A($d, 'ImpSaldoAnt') ?: '0');
                    $impPagado   = (float)str_replace(',', '', $A($d, 'ImpPagado') ?: '0');
                    $saldoInsol  = (float)str_replace(',', '', $A($d, 'ImpSaldoInsoluto') ?: '0');
                    $idDoc       = $A($d, 'IdDocumento') ?: '—';

                    $out['pagos'][] = [
                        'fecha_pago'     => $fechaPago,
                        'parcialidad'    => $parcialidad ?: null,
                        'saldo_anterior' => $saldoAnt,
                        'monto_pago'     => $impPagado ?: $monto, // a veces Monto global = ImpPagado
                        'saldo_insoluto' => $saldoInsol,
                        'documento'      => $idDoc,
                    ];
                    $sum += ($impPagado ?: $monto);
                }
            } else {
                // sin DoctoRelacionado
                $out['pagos'][] = [
                    'fecha_pago'     => $fechaPago,
                    'parcialidad'    => null,
                    'saldo_anterior' => null,
                    'monto_pago'     => $monto,
                    'saldo_insoluto' => null,
                    'documento'      => '—',
                ];
                $sum += $monto;
            }
        }

        $out['totales']['monto'] = $sum;
        return $out;
    }
}
