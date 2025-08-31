<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('timbre_cuentas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('rfc_id'); // referencia a rfc_usuarios.id
            $table->unsignedInteger('asignados_total')->default(0);
            $table->unsignedInteger('consumidos_total')->default(0);
            $table->timestamps();

            $table->unique('rfc_id');
            $table->foreign('rfc_id')->references('id')->on('rfc_usuarios')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('timbre_cuentas');
    }
};
