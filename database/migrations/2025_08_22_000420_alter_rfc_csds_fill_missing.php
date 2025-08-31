<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rfc_csds', function (Blueprint $table) {
            if (!Schema::hasColumn('rfc_csds', 'no_certificado')) $table->string('no_certificado', 40)->nullable()->after('nombre');
            if (!Schema::hasColumn('rfc_csds', 'vigencia_desde')) $table->date('vigencia_desde')->nullable()->after('no_certificado');
            if (!Schema::hasColumn('rfc_csds', 'vigencia_hasta')) $table->date('vigencia_hasta')->nullable()->after('vigencia_desde');
            if (!Schema::hasColumn('rfc_csds', 'cer_pem_path')) $table->string('cer_pem_path')->nullable()->after('key_path');
            if (!Schema::hasColumn('rfc_csds', 'key_pem_path')) $table->string('key_pem_path')->nullable()->after('cer_pem_path');
            if (!Schema::hasColumn('rfc_csds', 'key_password_enc')) $table->text('key_password_enc')->nullable()->after('key_pem_path');
            if (!Schema::hasColumn('rfc_csds', 'validado')) $table->boolean('validado')->default(false)->after('key_password_enc');
            if (!Schema::hasColumn('rfc_csds', 'revisado')) $table->boolean('revisado')->default(false)->after('validado');
            if (!Schema::hasColumn('rfc_csds', 'activo')) $table->boolean('activo')->default(false)->after('revisado');
        });
    }

    public function down(): void
    {
        // No hacemos down, porque no sabemos el estado previo exacto
    }
};
