<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas', 'uso_cfdi')) {
                $table->string('uso_cfdi', 5)->nullable()->after('forma_pago'); // Ajusta 'after' si prefieres
            }
        });

        Schema::table('factura_borradores', function (Blueprint $table) {
            if (!Schema::hasColumn('factura_borradores', 'uso_cfdi')) {
                $table->string('uso_cfdi', 5)->nullable()->after('forma_pago');
            }
        });
    }

    public function down()
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (Schema::hasColumn('facturas', 'uso_cfdi')) {
                $table->dropColumn('uso_cfdi');
            }
        });

        Schema::table('factura_borradores', function (Blueprint $table) {
            if (Schema::hasColumn('factura_borradores', 'uso_cfdi')) {
                $table->dropColumn('uso_cfdi');
            }
        });
    }
};
