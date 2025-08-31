<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('complementos', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('users_id');
            $table->unsignedBigInteger('rfc_usuario_id')->nullable(); // multi-RFC

            $table->dateTime('fecha')->nullable();
            $table->string('codigo_postal', 10)->nullable();

            // Receptor (snapshot)
            $table->string('rfc', 30);
            $table->string('razon_social', 200);

            // Estado
            $table->string('estatus', 20);
            $table->unsignedBigInteger('id_cancelar')->nullable();

            // Archivos (igual que facturas)
            $table->longText('xml')->nullable();            // TEXTO PLANO XML
            $table->longText('pdf')->nullable();            // base64 (normalmente)
            $table->longText('solicitud_timbre')->nullable();
            $table->string('uuid', 36)->nullable();
            $table->longText('acuse')->nullable();

            $table->timestamps();

            $table->index('users_id');
            $table->index('rfc_usuario_id');
            $table->index('estatus');
            $table->index('rfc');
            $table->unique('uuid');

            $table->foreign('rfc_usuario_id')->references('id')->on('rfc_usuarios')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complementos');
    }
};
