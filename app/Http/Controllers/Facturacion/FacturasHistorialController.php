<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FacturasHistorialController extends Controller
{
    public function index(Request $r)
{
    $items = Factura::query()
        ->with('rfcUsuario:id,rfc,razon_social')
        ->forActiveRfc()
        ->when($r->filled('tipo'), fn($q) => $q->where('tipo_comprobante',$r->tipo))
        ->when($r->filled('estatus'), fn($q) => $q->where('estatus',$r->estatus))
        ->when($r->filled('buscar'), function($q) use ($r){
            $t = '%'.$r->buscar.'%';
            $q->where(function($w) use ($t){
                $w->where('uuid','like',$t)
                  ->orWhere('razon_social','like',$t)
                  ->orWhere('rfc','like',$t);
            });
        })
        ->orderByDesc('fecha_factura')
        ->paginate(20)
        ->withQueryString();

    // Mapa RFC -> email desde la tabla clientes (del usuario actual)
    $emailsPorRfc = \App\Models\Cliente::query()
        ->where('users_id', auth()->id())
        ->whereIn('rfc', $items->pluck('rfc')->filter()->unique())
        ->pluck('email', 'rfc');

    return view('facturacion.historial.index', [
        'items' => $items,
        'emailsPorRfc' => $emailsPorRfc,
    ]);
}

    public function show(Factura $factura)
    {
        $this->authorizeRfc($factura);
        $cfdi = $this->parseCfdi($factura); // <-- conceptos + totales desde el XML
        return view('facturacion.historial.show', compact('factura','cfdi'));
    }


    public function descargarPdf(Factura $factura)
    {
        $this->authorizeRfc($factura);
        $data = $this->decodeB64($factura->pdf);
        abort_unless($data, 404, 'PDF no disponible');

        $nombre = $this->nombreArchivo($factura, 'pdf');
        return response()->streamDownload(function () use ($data) {
            echo $data;
        }, $nombre, ['Content-Type' => 'application/pdf']);
    }

    public function descargarXml(Factura $factura)
{
    $this->authorizeRfc($factura);

    // El campo XML viene como TEXTO PLANO (no base64)
    $xml = $factura->xml ? trim($factura->xml) : null;
    abort_unless($xml, 404, 'XML no disponible');

    // Si por error llegara en data URI o base64, intentamos decodificar
    if (!str_starts_with($xml, '<')) {
        if (str_contains($xml, 'base64,')) {
            $xml = substr($xml, strpos($xml, 'base64,') + 7);
        }
        $dec = base64_decode($xml, true);
        if ($dec !== false && str_starts_with(ltrim($dec), '<')) {
            $xml = $dec;
        }
    }

    $nombre = $this->nombreArchivo($factura, 'xml');

    return response()->streamDownload(function() use ($xml) {
        echo $xml;
    }, $nombre, [
        'Content-Type' => 'application/xml; charset=UTF-8'
    ]);
}

    public function enviarEmail(Request $r, Factura $factura)
{
    $this->authorizeRfc($factura);

    $r->validate([
        'to' => ['required','email'],
        'cc' => ['nullable','email'],
    ]);

    // PDF: normalmente base64 (o null)
    $pdf = $this->decodeB64($factura->pdf);
    // XML: en tu caso, TEXTO PLANO (pero con soporte a base64 si llega así)
    $xml = $factura->xml ? trim($factura->xml) : null;
    if ($xml && !str_starts_with($xml, '<')) {
        if (str_contains($xml, 'base64,')) {
            $xml = substr($xml, strpos($xml, 'base64,') + 7);
        }
        $dec = base64_decode($xml, true);
        if ($dec !== false && str_starts_with(ltrim($dec), '<')) {
            $xml = $dec;
        }
    }

    // Enviar usando una VISTA (corrige el TypeError de setBody)
    \Mail::send('emails.cfdi_plain', ['factura' => $factura], function($m) use ($r, $factura, $pdf, $xml) {
        $m->to($r->input('to'));
        if ($r->filled('cc')) $m->cc($r->input('cc'));

        $m->subject('CFDI '.$factura->tipo_comprobante.' '.$this->folioLike($factura));

        if ($pdf) {
            $m->attachData($pdf, $this->nombreArchivo($factura,'pdf'), ['mime' => 'application/pdf']);
        }
        if ($xml) {
            $m->attachData($xml, $this->nombreArchivo($factura,'xml'), ['mime' => 'application/xml; charset=UTF-8']);
        }
    });

    return back()->with('ok', 'Correo enviado.');
}


    // --- Helpers ---
    protected function decodeB64(?string $b64): ?string
    {
        if (!$b64) return null;
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
        return $f->uuid ?: ($f->razon_social.' '.optional($f->fecha_factura)->format('Y-m-d'));
    }

    protected function authorizeRfc(Factura $factura): void
    {
        abort_unless(optional($factura->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);
    }

    /** Retorna XML de la factura como string (texto plano, con soporte para base64/data URI). */
protected function xmlString(Factura $factura): ?string
{
    $xml = $factura->xml ? trim($factura->xml) : null;
    if (!$xml) return null;

    if (!str_starts_with($xml, '<')) {
        if (str_contains($xml, 'base64,')) {
            $xml = substr($xml, strpos($xml, 'base64,') + 7);
        }
        $dec = base64_decode($xml, true);
        if ($dec !== false && str_starts_with(ltrim($dec), '<')) {
            $xml = $dec;
        }
    }
    return $xml;
}

/** Parsea CFDI 3.3/4.0 y devuelve conceptos y totales listos para renderizar. */
protected function parseCfdi(Factura $factura): array
{
    $res = [
        'conceptos' => [],
        'totales' => [
            'subtotal'    => null,
            'descuento'   => null,
            'trasladados' => null,
            'retenidos'   => null,
            'total'       => null,
        ],
    ];

    $xml = $this->xmlString($factura);
    if (!$xml) return $res;

    libxml_use_internal_errors(true);
    $doc = @simplexml_load_string($xml);
    if (!$doc) return $res;

    $ns = $doc->getNamespaces(true);
    // Registrar prefijo cfdi (intenta 4.0; si no, 3.3)
    $doc->registerXPathNamespace('cfdi', $ns['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4');

    // Helper para leer atributos (con/ sin namespace)
    $A = function($el, $name) use ($ns) {
        $val = (string)($el[$name] ?? '');
        if ($val === '' && $ns && isset($ns['cfdi'])) {
            $atts = $el->attributes($ns['cfdi']);
            if (isset($atts[$name])) $val = (string)$atts[$name];
        }
        return $val;
    };

    // Totales (del Comprobante)
    $subtotal  = (float)str_replace(',', '', $A($doc, 'SubTotal'));
    $descuento = (float)str_replace(',', '', $A($doc, 'Descuento'));
    $total     = (float)str_replace(',', '', $A($doc, 'Total'));

    // Impuestos globales (si vienen)
    $tras = 0.0;
    foreach ($doc->xpath('//cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado') as $t) {
        $tras += (float)str_replace(',', '', $A($t, 'Importe'));
    }
    $ret = 0.0;
    foreach ($doc->xpath('//cfdi:Impuestos/cfdi:Retenciones/cfdi:Retencion') as $t) {
        $ret += (float)str_replace(',', '', $A($t, 'Importe'));
    }

    $res['totales'] = [
        'subtotal'    => $subtotal,
        'descuento'   => $descuento ?: 0.0,
        'trasladados' => $tras ?: 0.0,
        'retenidos'   => $ret ?: 0.0,
        'total'       => $total,
    ];

    // Conceptos
    $conceptos = $doc->xpath('//cfdi:Conceptos/cfdi:Concepto') ?: [];
    foreach ($conceptos as $c) {
        $res['conceptos'][] = [
            'clave_prod_serv' => $A($c, 'ClaveProdServ'),
            'descripcion'     => $A($c, 'Descripcion'),
            'cantidad'        => (float)str_replace(',', '', $A($c, 'Cantidad')),
            'unidad'          => $A($c, 'Unidad') ?: $A($c, 'ClaveUnidad'),
            'valor_unitario'  => (float)str_replace(',', '', $A($c, 'ValorUnitario')),
            'descuento'       => (float)str_replace(',', '', $A($c, 'Descuento') ?: '0'),
            'importe'         => (float)str_replace(',', '', $A($c, 'Importe')),
        ];
    }

    return $res;
}

}
