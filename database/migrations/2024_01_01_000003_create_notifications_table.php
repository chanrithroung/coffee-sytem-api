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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // order, stock, system, payment, table, user, error, success, warning, info
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data for the notification
            $table->boolean('read')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('related_type')->nullable(); // Model type this notification relates to
            $table->unsignedBigInteger('related_id')->nullable(); // ID of the related model
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->timestamp('expires_at')->nullable(); // When this notification expires
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'read']);
            $table->index(['type', 'created_at']);
            $table->index(['related_type', 'related_id']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
