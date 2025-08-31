<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('timbre_movimientos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('rfc_id');     // rfc_usuarios.id
            $table->unsignedBigInteger('user_id');    // users.id
            $table->enum('tipo', ['asignacion','timbrado','cancelacion','verificacion_cancelacion']);
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('referencia', 255)->nullable(); // folio, uuid, nota, etc.
            $table->timestamps();

            $table->index(['rfc_id','tipo','created_at']);
            $table->foreign('rfc_id')->references('id')->on('rfc_usuarios')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('timbre_movimientos');
    }
};
