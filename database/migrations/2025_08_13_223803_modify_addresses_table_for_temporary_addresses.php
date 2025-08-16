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
        Schema::table('addresses', function (Blueprint $table) {
            // Modificar user_id para que sea nullable
            // Esto permite direcciones temporales no asociadas a un usuario específico
            $table->foreignId('user_id')->nullable()->change();
            
            // Agregar campo para identificar direcciones temporales
            $table->boolean('is_temporary')->default(false)->after('is_default');
            
            // Agregar índices para mejorar performance
            $table->index(['user_id', 'is_default']);
            $table->index('is_temporary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['user_id', 'is_default']);
            $table->dropIndex(['is_temporary']);
            
            // Eliminar campo is_temporary
            $table->dropColumn('is_temporary');
            
            // Revertir user_id a no nullable
            // NOTA: Esto podría fallar si existen direcciones temporales en la DB
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
