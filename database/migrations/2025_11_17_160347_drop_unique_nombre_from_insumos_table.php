<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insumos', function (Blueprint $table) {
            // Elimina la restricción UNIQUE en el campo 'nombre'
            $table->dropUnique('insumos_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::table('insumos', function (Blueprint $table) {
            // Restaura la restricción UNIQUE si se hace rollback
            $table->unique('nombre', 'insumos_nombre_unique');
        });
    }
};