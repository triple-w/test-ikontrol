<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folio extends Model
{
    protected $table = 'folios';
    public $timestamps = false;

    protected $fillable = [
        'users_id',
        'tipo',
        'serie',
        'folio',
        'rfc_usuario_id',
    ];

    public function rfcUsuario()
    {
        return $this->belongsTo(\App\Models\RfcUsuario::class, 'rfc_usuario_id');
    }

    /** Filtra por RFC activo */
    public function scopeForActiveRfc($q)
    {
        if ($rfc = session('rfc_seleccionado')) {
            $q->whereHas('rfcUsuario', fn($qq) => $qq->where('rfc', $rfc));
        }
        return $q;
    }
}
