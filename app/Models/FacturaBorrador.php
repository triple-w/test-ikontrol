<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaBorrador extends Model
{
    protected $table = 'factura_borradores';

    protected $fillable = [
        'user_id','rfc_usuario_id','cliente_id',
        'tipo','serie','folio','fecha',
        'metodo_pago','forma_pago','comentarios_pdf',
        'subtotal','descuento','impuestos','total',
        'payload','estatus',
    ];

    protected $casts = [
        'fecha'   => 'datetime',
        'payload' => 'array',
    ];
}
