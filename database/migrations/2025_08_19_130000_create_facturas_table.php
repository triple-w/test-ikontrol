<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('facturas', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Compat + multi-RFC
            $table->unsignedBigInteger('users_id');                // dueño/autor
            $table->unsignedBigInteger('rfc_usuario_id')->nullable(); // emisor (RFC activo)

            // Snapshot receptor (como en FactuCare)
            $table->string('rfc', 30);
            $table->string('razon_social', 200);
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

            // Estado del CFDI / control
            $table->string('estatus', 20);                   // p.ej. borrador|timbrado|cancelado
            $table->unsignedBigInteger('id_cancelar')->nullable();

            // Fechas
            $table->dateTime('fecha')->nullable();           // genérico (creación/envío)
            $table->dateTime('fecha_factura')->nullable();   // fecha del CFDI

            // Archivos (base64)
            $table->longText('xml')->nullable();
            $table->longText('pdf')->nullable();
            $table->longText('solicitud_timbre')->nullable();
            $table->longText('acuse')->nullable();

            // Datos fiscales adicionales
            $table->decimal('descuento', 14, 2)->default(0);
            $table->string('uuid', 36)->nullable();          // timbre
            $table->string('nombre_comprobante', 100)->nullable();
            $table->string('tipo_comprobante', 10)->nullable(); // ingreso|egreso|traslado|pagos
            $table->text('comentarios_pdf')->nullable();

            // Índices
            $table->index('users_id');
            $table->index('rfc_usuario_id');
            $table->index('estatus');
            $table->index('tipo_comprobante');
            $table->unique('uuid'); // si en tu histórico hay duplicados, quita el unique

            $table->timestamps();

            // FK opcional (si existe rfc_usuarios)
            $table->foreign('rfc_usuario_id')->references('id')->on('rfc_usuarios')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::dropIfExists('facturas');
    }
};
