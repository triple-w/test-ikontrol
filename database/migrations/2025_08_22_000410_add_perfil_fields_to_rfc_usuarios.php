<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rfc_usuarios', function (Blueprint $table) {
            $table->string('regimen_fiscal', 3)->nullable()->after('razon_social');
            $table->string('cp_expedicion', 5)->nullable()->after('regimen_fiscal');

            $table->string('calle')->nullable()->after('cp_expedicion');
            $table->string('no_ext', 50)->nullable()->after('calle');
            $table->string('no_int', 50)->nullable()->after('no_ext');
            $table->string('colonia')->nullable()->after('no_int');
            $table->string('municipio')->nullable()->after('colonia');
            $table->string('localidad')->nullable()->after('municipio');
            $table->string('estado')->nullable()->after('localidad');
            $table->string('codigo_postal', 5)->nullable()->after('estado');

            $table->string('email')->nullable()->after('codigo_postal');
            $table->string('telefono', 50)->nullable()->after('email');

            $table->string('logo_path')->nullable()->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('rfc_usuarios', function (Blueprint $table) {
            $table->dropColumn([
                'regimen_fiscal','cp_expedicion',
                'calle','no_ext','no_int','colonia','municipio','localidad','estado','codigo_postal',
                'email','telefono','logo_path',
            ]);
        });
    }
};
