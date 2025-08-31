<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complemento extends Model
{
    public function pagos()
    {
        return $this->hasMany(ComplementoPago::class, 'users_complementos_id');
    }
    
    public function rfcUsuario()
{
    return $this->belongsTo(\App\Models\RfcUsuario::class, 'rfc_usuario_id');
}

public function scopeForActiveRfc($q)
    {
        if ($rfc = session('rfc_seleccionado')) {
            $q->whereHas('rfcUsuario', fn($qq) => $qq->where('rfc', $rfc));
        }
        return $q;
    }

// 1) Cache simple para el XML parseado
protected ?\SimpleXMLElement $cfdiCache = null;

// 2) Devuelve el XML como TEXTO (acepta data URI o base64 si viniera así)
public function xmlRaw(): ?string
{
    $xml = $this->xml ? trim($this->xml) : null;
    if (!$xml) return null;

    // Si no empieza con "<", intentamos tratarlo como base64 / data URI
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

// 3) (Opcional pero útil) raíz CFDI cacheada para otros usos
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
