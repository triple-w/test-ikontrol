<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('folios', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Igual a Factucare
            $table->unsignedBigInteger('users_id');          // compatibilidad (usuario dueño)
            $table->string('tipo', 10);                      // ingreso | egreso | traslado | pagos
            $table->string('serie', 10);                     // prefijo (letras/números)
            $table->unsignedBigInteger('folio')->default(0); // folio actual

            // Multi-RFC (aislar por RFC emisor)
            $table->unsignedBigInteger('rfc_usuario_id')->nullable();

            // Índices / Únicos
            $table->index('users_id');
            $table->index(['rfc_usuario_id','tipo','serie'], 'idx_folios_rfc_tipo_serie');
            $table->unique(['rfc_usuario_id','tipo','serie'], 'uniq_folios_rfc_tipo_serie');

            // FK opcional a rfc_usuarios
            $table->foreign('rfc_usuario_id', 'fk_folios_rfc_usuario_id')
                ->references('id')->on('rfc_usuarios')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folios');
    }
};
