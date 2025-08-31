<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('complementos_pagos', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Conservamos tu nombre legacy para la FK al complemento:
            $table->unsignedBigInteger('users_complementos_id'); // -> complementos.id

            // Documento relacionado (factura) - opcional:
            $table->unsignedBigInteger('documento_id')->nullable(); // -> facturas.id

            $table->dateTime('fecha_pago')->nullable();
            $table->unsignedInteger('parcialidad')->nullable();

            $table->decimal('saldo_anterior', 14, 2)->default(0);
            $table->decimal('monto_pago', 14, 2)->default(0);
            $table->decimal('saldo_insoluto', 14, 2)->default(0);

            $table->timestamps();

            $table->index('users_complementos_id');
            $table->index('documento_id');

            $table->foreign('users_complementos_id')->references('id')->on('complementos')->onDelete('cascade');
            $table->foreign('documento_id')->references('id')->on('facturas')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complementos_pagos');
    }
};
