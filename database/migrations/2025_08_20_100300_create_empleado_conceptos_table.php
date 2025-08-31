<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('empleado_conceptos', function (Blueprint $table) {
      $table->bigIncrements('id');

      $table->unsignedBigInteger('empleado_id');

      $table->string('tipo', 20);       // percepcion|deduccion|otro_pago|...
      $table->string('codigo', 5)->nullable();
      $table->string('clave', 30)->nullable();
      $table->string('concepto', 200)->nullable();

      $table->decimal('importe', 14, 2)->nullable();
      $table->decimal('importe_gravado', 14, 2)->nullable();
      $table->decimal('importe_exento', 14, 2)->nullable();

      $table->json('extra')->nullable();

      $table->timestamps();

      $table->index(['empleado_id','tipo']);
      $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');
    });
  }

  public function down(): void {
    Schema::dropIfExists('empleado_conceptos');
  }
};
