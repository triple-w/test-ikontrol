<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $fillable = [
        'users_id','rfc_usuario_id',
        'rfc','razon_social','calle','no_ext','no_int','colonia','municipio','localidad','estado',
        'codigo_postal','pais','telefono','nombre_contacto',
        'estatus','id_cancelar','fecha','xml','pdf','solicitud_timbre','descuento','uuid','acuse',
        'nombre_comprobante','tipo_comprobante','comentarios_pdf','fecha_factura',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_factura' => 'datetime',
        'descuento' => 'decimal:2',
    ];

    public function rfcUsuario()
    {
        return $this->belongsTo(\App\Models\RfcUsuario::class, 'rfc_usuario_id');
    }

    /** Muestra solo las facturas del RFC activo */
    public function scopeForActiveRfc($q)
    {
        if ($rfc = session('rfc_seleccionado')) {
            $q->whereHas('rfcUsuario', fn($qq) => $qq->where('rfc', $rfc));
        }
        return $q;
    }

    // --- Cache simple para no reparsear el XML múltiples veces por request ---
protected ?\SimpleXMLElement $cfdiCache = null;

/** Devuelve el XML crudo de DB como string (texto plano), con soporte por si viniera en base64/data URI). */
public function xmlRaw(): ?string
{
    $xml = $this->xml ? trim($this->xml) : null;
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

/** Root CFDI (SimpleXML), cacheado. */
public function cfdiRoot(): ?\SimpleXMLElement
{
    if ($this->cfdiCache !== null) return $this->cfdiCache;

    $raw = $this->xmlRaw();
    if (!$raw) return $this->cfdiCache = null;

    libxml_use_internal_errors(true);
    $node = @simplexml_load_string($raw);
    if ($node === false) return $this->cfdiCache = null;

    return $this->cfdiCache = $node;
}

/** Accessor: serie extraída del XML (CFDI 3.3/4.0). */
public function getSerieXmlAttribute(): ?string
{
    $n = $this->cfdiRoot();
    if (!$n) return null;
    $serie = (string)($n['Serie'] ?? '');
    return $serie !== '' ? $serie : null;
}

/** Accessor: folio extraído del XML. */
public function getFolioXmlAttribute(): ?string
{
    $n = $this->cfdiRoot();
    if (!$n) return null;
    $folio = (string)($n['Folio'] ?? '');
    return $folio !== '' ? $folio : null;
}

/** Accessor: total extraído del XML (como float). */
public function getTotalXmlAttribute(): ?float
{
    $n = $this->cfdiRoot();
    if (!$n) return null;
    $total = (string)($n['Total'] ?? '');
    if ($total === '') return null;
    return (float)str_replace(',', '', $total);
}

/** Accessor cómodo: "SERIE-FOLIO" (si alguno falta, devuelve el que exista). */
public function getSerieFolioAttribute(): ?string
{
    $serie = $this->serie_xml;
    $folio = $this->folio_xml;
    if ($serie && $folio) return $serie.'-'.$folio;
    if ($serie) return $serie;
    if ($folio) return $folio;
    return null;
}

protected $appends = ['monto_total'];

    public function getMontoTotalAttribute(): float
    {
        $xml = $this->xml;
        if (empty($xml)) return 0.0;

        // 1) Rápido: regex del atributo Total="nnnn.nn"
        if (preg_match('/\bTotal="([\d]+(?:\.[\d]+)?)"/', $xml, $m)) {
            return (float) $m[1];
        }

        // 2) Fallback: SimpleXML
        try {
            libxml_use_internal_errors(true);
            $sx = simplexml_load_string($xml);
            if ($sx !== false) {
                $attrs = $sx->attributes();
                if ($attrs && (isset($attrs['Total']) || isset($attrs['total']))) {
                    return (float) ($attrs['Total'] ?? $attrs['total']);
                }
            }
        } catch (\Throwable $e) {
            // swallow
        }
        return 0.0;
    }


}
