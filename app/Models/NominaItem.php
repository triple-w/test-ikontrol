<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NominaItem extends Model
{
    protected $table = 'nomina_items';

    protected $fillable = [
        'nomina_id','tipo','codigo','clave','concepto',
        'importe','importe_gravado','importe_exento','extra',
    ];

    protected $casts = [
        'importe'         => 'decimal:2',
        'importe_gravado' => 'decimal:2',
        'importe_exento'  => 'decimal:2',
        'extra'           => 'array',
    ];

    public function nomina() { return $this->belongsTo(Nomina::class, 'nomina_id'); }
}
