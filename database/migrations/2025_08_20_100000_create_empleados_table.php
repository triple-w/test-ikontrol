<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('empleados', function (Blueprint $table) {
      $table->bigIncrements('id');

      $table->unsignedBigInteger('users_id');        // dueño
      $table->unsignedBigInteger('rfc_usuario_id');  // emisor (multi-RFC)

      $table->string('nombre', 200);
      $table->string('rfc', 13)->nullable();
      $table->string('curp', 18)->nullable();
      $table->string('num_seguro_social', 20)->nullable();

      $table->string('calle', 100)->nullable();
      $table->string('localidad', 50)->nullable();
      $table->string('no_exterior', 20)->nullable();
      $table->string('no_interior', 20)->nullable();
      $table->string('referencia', 100)->nullable();
      $table->string('colonia', 50)->nullable();
      $table->string('estado', 50)->nullable();
      $table->string('municipio', 50)->nullable();
      $table->string('pais', 30)->nullable();
      $table->string('codigo_postal', 10)->nullable();
      $table->string('telefono', 30)->nullable();
      $table->string('email', 100)->nullable();

      $table->string('registro_patronal', 20)->nullable();
      $table->string('tipo_contrato', 3)->nullable();       // c_TipoContrato
      $table->string('numero_empleado', 20)->nullable();
      $table->string('riesgo_puesto', 1)->nullable();       // c_RiesgoPuesto
      $table->string('tipo_jornada', 2)->nullable();        // c_TipoJornada
      $table->string('puesto', 100)->nullable();
      $table->date('fecha_inicio_laboral')->nullable();
      $table->string('tipo_regimen', 2)->nullable();        // c_TipoRegimen
      $table->decimal('salario', 14, 2)->default(0);
      $table->string('periodicidad_pago', 2)->nullable();   // c_PeriodicidadPago
      $table->decimal('salario_diario_integrado', 14, 2)->default(0);
      $table->string('clabe', 18)->nullable();
      $table->string('banco', 3)->nullable();               // c_Banco

      $table->timestamps();

      $table->index(['users_id','rfc_usuario_id']);
      $table->foreign('rfc_usuario_id')->references('id')->on('rfc_usuarios')->onDelete('cascade');
    });
  }

  public function down(): void {
    Schema::dropIfExists('empleados');
  }
};
