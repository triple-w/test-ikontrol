<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class RfcCsd extends Model
{
protected $table = 'rfc_csds';


protected $fillable = [
'rfc_usuario_id','nombre','no_certificado','vigencia_desde','vigencia_hasta',
'cer_path','key_path','key_password_enc','activo',
];


public function rfcUsuario(){ return $this->belongsTo(RfcUsuario::class, 'rfc_usuario_id'); }
}