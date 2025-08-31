<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class ClaveProdServ extends Model
{
    protected $table = 'clave_prod_serv';
    public $timestamps = false;
    protected $fillable = ['clave','descripcion'];
}
