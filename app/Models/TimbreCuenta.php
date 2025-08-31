<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimbreCuenta extends Model
{
    protected $table = 'timbre_cuentas';

    protected $fillable = [
        'rfc_id', 'asignados_total', 'consumidos_total',
    ];

    public function rfc()
    {
        return $this->belongsTo(RfcUsuario::class, 'rfc_id');
    }

    public function getDisponiblesAttribute(): int
    {
        return max(0, (int)$this->asignados_total - (int)$this->consumidos_total);
    }
}
