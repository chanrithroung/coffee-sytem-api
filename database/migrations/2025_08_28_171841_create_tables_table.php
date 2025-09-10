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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('table_number')->unique();
            $table->string('name')->nullable();
            $table->integer('capacity');
            $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance', 'cleaning'])->default('available');
            $table->string('location')->nullable(); // floor, section, etc.
            $table->decimal('position_x', 8, 2)->nullable(); // for floor plan positioning
            $table->decimal('position_y', 8, 2)->nullable();
            $table->string('qr_code')->nullable(); // for QR code ordering
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'is_active']);
            $table->index('table_number');
            $table->index('qr_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
