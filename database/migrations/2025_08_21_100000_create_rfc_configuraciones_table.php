<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('rfc_configuraciones', function (Blueprint $table) {
$table->bigIncrements('id');
$table->unsignedBigInteger('rfc_usuario_id')->unique(); // 1 a 1 con el RFC


// Datos fiscales
$table->string('nombre_comercial', 200)->nullable();
$table->string('regimen_fiscal', 3)->nullable(); // c_RegimenFiscal emisor
$table->string('lugar_expedicion', 5)->nullable(); // CP


// Contacto
$table->string('telefono', 30)->nullable();
$table->string('email', 100)->nullable();


// Preferencias CFDI
$table->string('cfdi_version', 4)->default('4.0');
$table->string('moneda_default', 3)->default('MXN');
$table->decimal('tipo_cambio_default', 12, 6)->nullable();
$table->string('metodo_pago_default', 3)->nullable(); // c_MetodoPago
$table->string('forma_pago_default', 3)->nullable(); // c_FormaPago
$table->string('serie_default', 10)->nullable();
$table->text('condiciones_pago')->nullable();
$table->text('leyendas_globales')->nullable();


// Branding
$table->string('logo_path')->nullable(); // storage path privado


// CSD activo (FK suave)
$table->unsignedBigInteger('csd_activo_id')->nullable();


// PAC (opcional por RFC)
$table->string('pac_proveedor', 50)->nullable();
$table->string('pac_usuario', 100)->nullable();
$table->text('pac_password_enc')->nullable(); // cifrado con encrypt()


// SMTP opcional por RFC
$table->string('smtp_host', 120)->nullable();
$table->unsignedSmallInteger('smtp_port')->nullable();
$table->string('smtp_user', 120)->nullable();
$table->text('smtp_password_enc')->nullable(); // cifrado
$table->string('smtp_encryption', 10)->nullable(); // tls/ssl
$table->string('smtp_from_email', 120)->nullable();
$table->string('smtp_from_name', 120)->nullable();


$table->timestamps();


$table->foreign('rfc_usuario_id')->references('id')->on('rfc_usuarios')->onDelete('cascade');
});
}


public function down(): void {
Schema::dropIfExists('rfc_configuraciones');
}
};