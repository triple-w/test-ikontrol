<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('factura_borradores', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('rfc_usuario_id')->nullable();
            $t->unsignedBigInteger('cliente_id');

            $t->enum('tipo', ['I','E']);
            $t->string('serie', 20)->nullable();
            $t->string('folio', 50)->nullable();
            $t->dateTime('fecha');

            $t->string('metodo_pago', 3); // PUE/PPD
            $t->string('forma_pago', 3);  // 01..99

            $t->text('comentarios_pdf')->nullable();

            $t->decimal('subtotal', 14, 2)->default(0);
            $t->decimal('descuento', 14, 2)->default(0);
            $t->decimal('impuestos', 14, 2)->default(0);
            $t->decimal('total', 14, 2)->default(0);

            $t->json('payload'); // todo el JSON de la factura

            $t->string('estatus', 20)->default('borrador'); // borrador | timbrado | cancelado | etc.
            $t->timestamps();

            $t->index(['rfc_usuario_id','cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_borradores');
    }
};
