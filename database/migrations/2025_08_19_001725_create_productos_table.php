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
        Schema::create('productos', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Campos de Factucare
            $table->unsignedBigInteger('users_id'); // compatibilidad
            $table->unsignedBigInteger('rfc_usuario_id')->nullable(); // << sin AFTER

            $table->string('clave', 50)->nullable();
            $table->string('unidad', 20)->nullable();
            $table->decimal('precio', 12, 2)->default(0);
            $table->string('descripcion', 255);
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('clave_prod_serv_id')->nullable();
            $table->unsignedBigInteger('clave_unidad_id')->nullable();

            // Índices
            $table->index('rfc_usuario_id', 'idx_prod_rfc_usuario_id');
            $table->index('users_id');
            $table->index('clave');
            $table->index('descripcion');

            // FK a RFCs
            $table->foreign('rfc_usuario_id', 'fk_prod_rfc_usuario_id')
                ->references('id')->on('rfc_usuarios')
                ->onDelete('cascade');
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
