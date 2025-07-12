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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique(); // Slug para URL amigable
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->decimal('price', 10, 2)->default(0.00); // Precio del producto
            $table->integer('stock')->default(0);
            $table->foreignId('category_id')->constrained()->onDelete('cascade')->default(1); // Asumiendo que hay una categoría por defecto
            $table->string('image_url')->default('storage/images/noImg.jpg'); // URL de la imagen del producto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
