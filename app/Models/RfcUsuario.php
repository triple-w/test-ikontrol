<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RfcUsuario extends Model
{
    use HasFactory;

    protected $table = 'rfc_usuarios';

    protected $fillable = [
        'user_id',
        'rfc',
        'razon_social',
        // NUEVOS CAMPOS PERFIL:
        'regimen_fiscal','cp_expedicion',
        'calle','no_ext','no_int','colonia','municipio','localidad','estado','codigo_postal',
        'email','telefono','logo_path',
    ];

    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function csds()
    {
        return $this->hasMany(RfcCsd::class, 'rfc_usuario_id');
    }

    public function csdActivo(){ return $this->hasOne(\App\Models\RfcCsd::class, 'rfc_usuario_id')->where('activo', 1); }

}
