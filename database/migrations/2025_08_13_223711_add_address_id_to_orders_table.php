<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Agregar foreign key nullable para address
            // Nullable permite órdenes sin dirección específica (usando dirección por defecto del usuario)
            $table->foreignId('address_id')->nullable()->constrained('addresses')->onDelete('set null');
            
            // Índice para mejorar performance en consultas
            $table->index('address_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Primero eliminar el índice
            $table->dropIndex(['address_id']);
            
            // Luego eliminar la foreign key y la columna
            $table->dropForeign(['address_id']);
            $table->dropColumn('address_id');
        });
    }
};
