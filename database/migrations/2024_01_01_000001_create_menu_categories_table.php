<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('icon', 10)->default('☕');
            $table->string('color', 7)->default('#8B4513');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->time('available_from')->nullable();
            $table->time('available_to')->nullable();
            $table->json('available_days'); // Removed default value
            $table->timestamps();

            $table->index(['is_active', 'is_visible']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_categories');
    }
};
