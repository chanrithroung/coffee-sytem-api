<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('menu_categories')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->boolean('is_on_sale')->default(false);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('tags')->nullable();
            $table->json('allergens')->nullable();
            $table->json('nutrition_info')->nullable();
            $table->integer('preparation_time')->default(5); // in minutes
            $table->json('customizations')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'is_available', 'is_visible']);
            $table->index(['is_featured', 'is_available']);
            $table->index('sort_order');
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
