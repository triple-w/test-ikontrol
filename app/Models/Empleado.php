<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'users_id','rfc_usuario_id',
        'nombre','rfc','curp','num_seguro_social',
        'calle','localidad','no_exterior','no_interior','referencia','colonia','estado','municipio','pais','codigo_postal',
        'telefono','email','registro_patronal','tipo_contrato','numero_empleado','riesgo_puesto','tipo_jornada','puesto',
        'fecha_inicio_laboral','tipo_regimen','salario','periodicidad_pago','salario_diario_integrado','clabe','banco',
    ];

    protected $casts = [
        'fecha_inicio_laboral' => 'date',
        'salario' => 'decimal:2',
        'salario_diario_integrado' => 'decimal:2',
    ];

    public function rfcUsuario()  { return $this->belongsTo(RfcUsuario::class, 'rfc_usuario_id'); }
    public function nominas()     { return $this->hasMany(Nomina::class, 'empleado_id'); }
    public function conceptos()   { return $this->hasMany(EmpleadoConcepto::class, 'empleado_id'); }

    public function scopeForActiveRfc($q)
    {
        if ($rfc = session('rfc_seleccionado')) {
            $q->whereHas('rfcUsuario', fn($qq) => $qq->where('rfc',$rfc));
        }
        return $q;
    }
}
