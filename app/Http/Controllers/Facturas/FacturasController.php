<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\RfcUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacturasHistorialController extends Controller
{
    public function index(Request $r)
    {
        $items = Factura::query()
            ->with('rfcUsuario:id,rfc,razon_social')
            ->forActiveRfc()
            ->when($r->filled('tipo'), fn($q)=>$q->where('tipo_comprobante',$r->tipo))
            ->when($r->filled('estatus'), fn($q)=>$q->where('estatus',$r->estatus))
            ->when($r->filled('buscar'), function($q) use ($r){
                $t = '%'.$r->buscar.'%';
                $q->where(function($w) use ($t){
                    $w->where('uuid','like',$t)
                      ->orWhere('razon_social','like',$t)
                      ->orWhere('rfc','like',$t);
                });
            })
            ->orderByDesc('fecha_factura')
            ->paginate(20)->withQueryString();

        return view('facturacion.historial.index', compact('items'));
    }

    public function show(Factura $factura)
    {
        $this->authorizeRfc($factura);
        return view('facturacion.historial.show', compact('factura'));
    }

    public function descargarPdf(Factura $factura)
    {
        $this->authorizeRfc($factura);
        $data = $this->decodeB64($factura->pdf);
        abort_unless($data, 404, 'PDF no disponible');

        $nombre = $this->nombreArchivo($factura, 'pdf');
        return response()->streamDownload(function() use ($data){
            echo $data;
        }, $nombre, ['Content-Type' => 'application/pdf']);
    }

    public function descargarXml(Factura $factura)
    {
        $this->authorizeRfc($factura);
        $data = $this->decodeB64($factura->xml);
        abort_unless($data, 404, 'XML no disponible');

        $nombre = $this->nombreArchivo($factura, 'xml');
        return response()->streamDownload(function() use ($data){
            echo $data;
        }, $nombre, ['Content-Type' => 'application/xml']);
    }

    public function enviarEmail(Request $r, Factura $factura)
    {
        $this->authorizeRfc($factura);
        $r->validate([
            'to' => ['required','email'],
            'cc' => ['nullable','email'],
        ]);

        $pdf = $this->decodeB64($factura->pdf);
        $xml = $this->decodeB64($factura->xml);

        Mail::send([], [], function($m) use ($r,$factura,$pdf,$xml){
            $m->to($r->input('to'));
            if ($r->filled('cc')) $m->cc($r->input('cc'));
            $m->subject('CFDI '.$factura->tipo_comprobante.' '.$this->folioLike($factura));
            $m->setBody(
                "Estimado(a),\n\nAdjunto envío CFDI.\nUUID: {$factura->uuid}\nReceptor: {$factura->razon_social} ({$factura->rfc})\n\nSaludos.",
                'text/plain'
            );
            if ($pdf) $m->attachData($pdf, $this->nombreArchivo($factura,'pdf'), ['mime' => 'application/pdf']);
            if ($xml) $m->attachData($xml, $this->nombreArchivo($factura,'xml'), ['mime' => 'application/xml']);
        });

        return back()->with('ok', 'Correo enviado.');
    }

    // — Helpers —
    protected function decodeB64(?string $b64): ?string
    {
        if (!$b64) return null;
        // Soporta "data:application/pdf;base64,xxxx"
        if (str_contains($b64, 'base64,')) {
            $b64 = substr($b64, strpos($b64, 'base64,') + 7);
        }
        $raw = base64_decode($b64, true);
        return $raw === false ? null : $raw;
    }

    protected function nombreArchivo(Factura $f, string $ext): string
    {
        $base = $f->nombre_comprobante
            ?: ($f->uuid ?: ('CFDI_'.$f->tipo_comprobante.'_'.optional($f->fecha_factura)->format('Ymd_His')));
        return trim($base ?: 'comprobante').'.'.$ext;
    }

    protected function folioLike(Factura $f): string
    {
        // Si en tu XML guardas serie/folio, puedes parsearlo después.
        return $f->uuid ?: ($f->razon_social.' '.optional($f->fecha_factura)->format('Y-m-d'));
    }

    protected function authorizeRfc(Factura $factura): void
    {
        abort_unless(optional($factura->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);
    }
}
