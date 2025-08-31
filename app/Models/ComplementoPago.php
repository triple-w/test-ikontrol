<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplementoPago extends Model
{
    // Nombre de la tabla
    protected $table = 'complementos_pagos';

    // Asignación masiva
    protected $fillable = [
        'users_complementos_id', // FK -> complementos.id
        'documento_id',          // FK -> facturas.id (opcional)
        'fecha_pago',
        'parcialidad',
        'saldo_anterior',
        'monto_pago',
        'saldo_insoluto',
    ];

    // Casts
    protected $casts = [
        'fecha_pago'     => 'datetime',
        'parcialidad'    => 'integer',
        'saldo_anterior' => 'decimal:2',
        'monto_pago'     => 'decimal:2',
        'saldo_insoluto' => 'decimal:2',
    ];

    /** Relación: pertenece a un Complemento */
    public function complemento()
    {
        return $this->belongsTo(Complemento::class, 'users_complementos_id');
    }

    /** Relación: pertenece a una Factura (documento relacionado) */
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'documento_id');
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
}
