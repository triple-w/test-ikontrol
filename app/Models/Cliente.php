<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    public $timestamps = false; // tu tabla no tiene created_at/updated_at

    protected $fillable = [
        'rfc',
        'razon_social',
        'calle',
        'no_ext',
        'no_int',
        'colonia',
        'municipio',
        'localidad',
        'estado',
        'codigo_postal',
        'pais',
        'telefono',
        'nombre_contacto',
        'email',
        'users_id',
        'regimen_fiscal',
        'rfc_usuario_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function rfcUsuario()
    {
        return $this->belongsTo(\App\Models\RfcUsuario::class, 'rfc_usuario_id');
    }

    public function scopeForActiveRfc($query)
    {
        if ($rfc = session('rfc_seleccionado')) {
            $query->whereHas('rfcUsuario', function ($q) use ($rfc) {
                $q->where('rfc', $rfc);
            });
        }
        return $query;
    }
}
