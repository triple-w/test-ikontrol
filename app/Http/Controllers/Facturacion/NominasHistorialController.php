<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Nomina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NominasHistorialController extends Controller
{
    public function index(Request $r)
    {
        $items = Nomina::with(['empleado:id,nombre,rfc','rfcUsuario:id,rfc,razon_social'])
            ->forActiveRfc()
            ->when($r->filled('estatus'), fn($q) => $q->where('estatus',$r->estatus))
            ->when($r->filled('buscar'), function($q) use ($r){
                $t = '%'.$r->buscar.'%';
                $q->where(function($w) use ($t){
                    $w->where('uuid','like',$t)
                      ->orWhereHas('empleado', fn($qq) => $qq->where('nombre','like',$t)->orWhere('rfc','like',$t));
                });
            })
            ->orderByDesc('fecha')
            ->paginate(20)
            ->withQueryString();

        return view('facturacion.nominas.index', compact('items'));
    }

    public function show(Nomina $nomina)
    {
        $this->authorizeRfc($nomina);

        // Parseo rápido del complemento nómina 1.2 para mostrar renglones y totales
        $parsed = $this->parseNominaXml($nomina);

        return view('facturacion.nominas.show', [
            'nomina' => $nomina,
            'parsed' => $parsed,
        ]);
    }

    public function descargarPdf(Nomina $nomina)
    {
        $this->authorizeRfc($nomina);
        $data = $this->decodeB64($nomina->pdf);
        abort_unless($data, 404, 'PDF no disponible');

        $nombre = $this->nombreArchivo($nomina,'pdf');
        return response()->streamDownload(function() use ($data){ echo $data; }, $nombre, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function descargarXml(Nomina $nomina)
    {
        $this->authorizeRfc($nomina);
        $xml = $nomina->xmlRaw();
        abort_unless($xml, 404, 'XML no disponible');

        $nombre = $this->nombreArchivo($nomina,'xml');
        return response()->streamDownload(function() use ($xml){ echo $xml; }, $nombre, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    // ------------ Helpers compartidos -----------

    protected function decodeB64(?string $b64): ?string
    {
        if (!$b64) return null;
        if (str_contains($b64, 'base64,')) {
            $b64 = substr($b64, strpos($b64, 'base64,') + 7);
        }
        $raw = base64_decode($b64, true);
        return $raw === false ? null : $raw;
    }

    protected function nombreArchivo(Nomina $n, string $ext): string
    {
        $base = $n->uuid ?: ('Nomina_'.optional($n->fecha)->format('Ymd_His'));
        return trim($base ?: 'nomina').'.'.$ext;
    }

    protected function authorizeRfc(Nomina $n): void
    {
        abort_unless(optional($n->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);
    }

    protected function parseNominaXml(Nomina $n): array
    {
        $out = [
            'percepciones' => [],
            'deducciones'  => [],
            'otros_pagos'  => [],
            'totales'      => [
                'per_grav' => 0, 'per_exen' => 0, 'ded' => 0, 'otros' => 0, 'neto' => 0,
            ],
        ];

        $doc = $n->cfdiRoot();
        if (!$doc) return $out;

        $ns = $doc->getNamespaces(true);
        $doc->registerXPathNamespace('cfdi', $ns['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4');
        $doc->registerXPathNamespace('nom',  $ns['nomina12'] ?? 'http://www.sat.gob.mx/nomina12');

        $A = fn($el,$name) => (string)($el[$name] ?? '');

        // Percepciones
        foreach ($doc->xpath('//cfdi:Complemento/nom:Nomina/nom:Percepciones/nom:Percepcion') ?: [] as $p) {
            $fila = [
                'codigo'   => $A($p,'TipoPercepcion'),
                'clave'    => $A($p,'Clave'),
                'concepto' => $A($p,'Concepto'),
                'gravado'  => (float)str_replace(',', '', $A($p,'ImporteGravado') ?: '0'),
                'exento'   => (float)str_replace(',', '', $A($p,'ImporteExento')  ?: '0'),
            ];
            $out['percepciones'][] = $fila;
            $out['totales']['per_grav'] += $fila['gravado'];
            $out['totales']['per_exen'] += $fila['exento'];
        }

        // Deducciones
        foreach ($doc->xpath('//cfdi:Complemento/nom:Nomina/nom:Deducciones/nom:Deduccion') ?: [] as $d) {
            $fila = [
                'codigo'   => $A($d,'TipoDeduccion'),
                'clave'    => $A($d,'Clave'),
                'concepto' => $A($d,'Concepto'),
                'importe'  => (float)str_replace(',', '', $A($d,'Importe') ?: '0'),
            ];
            $out['deducciones'][] = $fila;
            $out['totales']['ded'] += $fila['importe'];
        }

        // OtrosPagos
        foreach ($doc->xpath('//cfdi:Complemento/nom:Nomina/nom:OtrosPagos/nom:OtroPago') ?: [] as $o) {
            $fila = [
                'codigo'   => $A($o,'TipoOtroPago'),
                'clave'    => $A($o,'Clave'),
                'concepto' => $A($o,'Concepto'),
                'importe'  => (float)str_replace(',', '', $A($o,'Importe') ?: '0'),
            ];
            $out['otros_pagos'][] = $fila;
            $out['totales']['otros'] += $fila['importe'];
        }

        $out['totales']['neto'] =
            ($out['totales']['per_grav'] + $out['totales']['per_exen'])
            - $out['totales']['ded']
            + $out['totales']['otros'];

        return $out;
    }
}
