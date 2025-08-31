<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Si existe, la eliminamos para dejarla limpia
        Schema::dropIfExists('clientes');

        // 2) Creamos la tabla idéntica a tu DDL de Factucare
        Schema::create('clientes', function (Blueprint $table) {
            // PK
            $table->bigIncrements('id');

            // Campos principales
            $table->string('rfc', 30);
            $table->string('razon_social', 200);

            // Domicilio / contacto
            $table->string('calle', 100)->nullable();
            $table->string('no_ext', 20)->nullable();
            $table->string('no_int', 20)->nullable();
            $table->string('colonia', 50)->nullable();
            $table->string('municipio', 50)->nullable();
            $table->string('localidad', 50)->nullable();
            $table->string('estado', 50)->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('pais', 30)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('nombre_contacto', 150)->nullable();
            $table->string('email', 90)->nullable();

            // Relación original (users_id → users.id)
            $table->unsignedBigInteger('users_id');

            // Campo régimen fiscal (tal cual)
            $table->string('regimen_fiscal', 5)->default('');

            // Índice y FK con nombres equivalentes a los de tu dump
            $table->index('users_id', 'IDX_50FE07D767B3B43D');
            $table->foreign('users_id', 'FK_50FE07D767B3B43D')
                  ->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
