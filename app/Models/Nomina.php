<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nomina extends Model
{
    protected $table = 'nominas';

    protected $fillable = [
        'users_id','rfc_usuario_id','empleado_id','estatus','uuid','fecha',
        'solicitud_timbre','xml','pdf','acuse',
        'total_percepciones_grav','total_percepciones_exen','total_deducciones','total_otros_pagos','total',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'total_percepciones_grav' => 'decimal:2',
        'total_percepciones_exen' => 'decimal:2',
        'total_deducciones'       => 'decimal:2',
        'total_otros_pagos'       => 'decimal:2',
        'total'                   => 'decimal:2',
    ];

    public function rfcUsuario() { return $this->belongsTo(RfcUsuario::class, 'rfc_usuario_id'); }
    public function empleado()   { return $this->belongsTo(Empleado::class, 'empleado_id'); }
    public function items()      { return $this->hasMany(NominaItem::class, 'nomina_id'); }

    public function scopeForActiveRfc($q)
    {
        if ($rfc = session('rfc_seleccionado')) {
            $q->whereHas('rfcUsuario', fn($qq) => $qq->where('rfc',$rfc));
        }
        return $q;
    }

    // === XML helpers (texto plano con fallback base64) ===
    protected ?\SimpleXMLElement $cfdiCache = null;

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
}
