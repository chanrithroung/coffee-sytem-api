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
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('sku')->unique()->nullable();
            $table->string('barcode')->unique()->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(10);
            $table->string('unit')->default('piece'); 
            $table->json('images')->nullable(); 
            $table->boolean('is_active')->default(true);
            $table->boolean('track_stock')->default(true);
            $table->integer('preparation_time')->default(5); 
            $table->json('variants')->nullable(); 
            $table->json('nutrition_info')->nullable();
            $table->json('allergens')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['category_id', 'is_active']);
            $table->index(['is_active', 'stock_quantity']);
            $table->index('slug');
            $table->index('sku');
            $table->index('barcode');
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
