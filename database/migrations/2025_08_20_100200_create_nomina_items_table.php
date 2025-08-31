<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('nomina_items', function (Blueprint $table) {
      $table->bigIncrements('id');

      $table->unsignedBigInteger('nomina_id');

      // Tipo de renglón: percepcion|deduccion|otro_pago|incapacidad|horas_extra|separacion|jubilacion
      $table->string('tipo', 20);

      // Catálogos SAT: para percepciones es c_TipoPercepcion, deducciones c_TipoDeduccion, etc.
      $table->string('codigo', 5)->nullable();

      // Campos “legacy” que ya usas:
      $table->string('clave', 30)->nullable();
      $table->string('concepto', 200)->nullable();

      // Importes genéricos
      $table->decimal('importe', 14, 2)->nullable();            // p/ deducciones y otros pagos
      $table->decimal('importe_gravado', 14, 2)->nullable();    // p/ percepciones
      $table->decimal('importe_exento', 14, 2)->nullable();     // p/ percepciones

      // Atributos específicos (ej. horas, días, tipoIncapacidad, SubsidioCausado, etc.)
      $table->json('extra')->nullable();

      $table->timestamps();

      $table->index(['nomina_id','tipo']);
      $table->foreign('nomina_id')->references('id')->on('nominas')->onDelete('cascade');
    });
  }

  public function down(): void {
    Schema::dropIfExists('nomina_items');
  }
};
