<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('rfc_configuraciones');
    }

    public function down(): void
    {
        // Si necesitas restaurarla, aquí iría el create original.
    }
};
