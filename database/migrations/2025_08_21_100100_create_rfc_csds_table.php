<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void {
Schema::create('rfc_csds', function (Blueprint $table) {
$table->bigIncrements('id');
$table->unsignedBigInteger('rfc_usuario_id');


$table->string('nombre', 120)->nullable();
$table->string('no_certificado', 20)->nullable();
$table->date('vigencia_desde')->nullable();
$table->date('vigencia_hasta')->nullable();


// rutas a storage privado (no público)
$table->string('cer_path');
$table->string('key_path');
$table->text('key_password_enc'); // encrypt()


$table->boolean('activo')->default(false);


$table->timestamps();
$table->index(['rfc_usuario_id','activo']);
$table->foreign('rfc_usuario_id')->references('id')->on('rfc_usuarios')->onDelete('cascade');
});
}


public function down(): void {
Schema::dropIfExists('rfc_csds');
}
};