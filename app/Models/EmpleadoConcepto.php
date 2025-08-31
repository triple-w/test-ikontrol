<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpleadoConcepto extends Model
{
    protected $table = 'empleado_conceptos';

    protected $fillable = [
        'empleado_id','tipo','codigo','clave','concepto',
        'importe','importe_gravado','importe_exento','extra',
    ];

    protected $casts = [
        'importe'         => 'decimal:2',
        'importe_gravado' => 'decimal:2',
        'importe_exento'  => 'decimal:2',
        'extra'           => 'array',
    ];

    public function empleado() { return $this->belongsTo(Empleado::class, 'empleado_id'); }
}
