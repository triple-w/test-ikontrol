<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('rfc_usuarios', function (Blueprint $table) {
        $table->id();
        // IMPORTANTe: que coincida el tipo con users.id (bigIncrements sin signo)
        $table->unsignedBigInteger('user_id');
        $table->string('rfc', 13);
        $table->string('razon_social');
        $table->timestamps();

        $table->foreign('user_id')
              ->references('id')
              ->on('users')
              ->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfc_usuarios');
    }
};
