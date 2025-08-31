<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class ClaveUnidad extends Model
{
    protected $table = 'clave_unidad';
    public $timestamps = false;
    protected $fillable = ['clave','descripcion'];
}
