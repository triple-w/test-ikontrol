<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('nominas', function (Blueprint $table) {
      $table->bigIncrements('id');

      $table->unsignedBigInteger('users_id');
      $table->unsignedBigInteger('rfc_usuario_id'); // emisor
      $table->unsignedBigInteger('empleado_id');    // receptor empleado

      $table->string('estatus', 20);                // borrador|timbrado|cancelado
      $table->string('uuid', 36)->nullable();
      $table->dateTime('fecha')->nullable();

      $table->longText('solicitud_timbre')->nullable();
      $table->longText('xml')->nullable();          // TEXTO PLANO XML nómina 1.2
      $table->longText('pdf')->nullable();          // base64
      $table->longText('acuse')->nullable();

      // (opcionales) totales para listar rápido:
      $table->decimal('total_percepciones_grav', 14, 2)->nullable();
      $table->decimal('total_percepciones_exen', 14, 2)->nullable();
      $table->decimal('total_deducciones', 14, 2)->nullable();
      $table->decimal('total_otros_pagos', 14, 2)->nullable();
      $table->decimal('total', 14, 2)->nullable();

      $table->timestamps();

      $table->unique('uuid');
      $table->index(['users_id','rfc_usuario_id','empleado_id']);
      $table->foreign('rfc_usuario_id')->references('id')->on('rfc_usuarios')->onDelete('cascade');
      $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');
    });
  }

  public function down(): void {
    Schema::dropIfExists('nominas');
  }
};
