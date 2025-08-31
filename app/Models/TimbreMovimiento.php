<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimbreMovimiento extends Model
{
    protected $table = 'timbre_movimientos';

    protected $fillable = [
        'rfc_id','user_id','tipo','cantidad','referencia',
    ];

    public function rfc()
    {
        return $this->belongsTo(RfcUsuario::class, 'rfc_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
