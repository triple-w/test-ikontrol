<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // lo agregamos NULL para transición (backfill), luego lo podremos hacer NOT NULL
            $table->unsignedBigInteger('rfc_usuario_id')->nullable()->after('users_id');

            $table->index('rfc_usuario_id', 'idx_clientes_rfc_usuario_id');
            $table->foreign('rfc_usuario_id', 'fk_clientes_rfc_usuario_id')
                  ->references('id')->on('rfc_usuarios')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign('fk_clientes_rfc_usuario_id');
            $table->dropIndex('idx_clientes_rfc_usuario_id');
            $table->dropColumn('rfc_usuario_id');
        });
    }
};
