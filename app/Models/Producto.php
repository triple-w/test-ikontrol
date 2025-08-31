<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';
    public $timestamps = false;

    protected $fillable = [
        'users_id','clave','unidad','precio','descripcion','observaciones',
        'clave_prod_serv_id','clave_unidad_id','rfc_usuario_id',
    ];

    public function rfcUsuario()
    {
        return $this->belongsTo(\App\Models\RfcUsuario::class, 'rfc_usuario_id');
    }

    public function prodServ()
    {
        return $this->belongsTo(\App\Models\Catalogos\ClaveProdServ::class, 'clave_prod_serv_id');
    }

    public function unidadSat()
    {
        return $this->belongsTo(\App\Models\Catalogos\ClaveUnidad::class, 'clave_unidad_id');
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
