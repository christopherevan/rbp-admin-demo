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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('loggable');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('action', 40);
            $table->string('description', 500);
            $table->json('properties')->nullable();
            $table->timestamp('created_at');
        });

        // One counter per document kind per day, bumped atomically. Replaces
        // the original's "count today's rows + 1", which could repeat numbers.
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->string('name', 60)->primary();
            $table->unsignedInteger('last_value');
        });

        Schema::create('catalog_products', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('supplier', 40);
            $table->string('title', 200);
            $table->string('category', 80);
            $table->string('family', 40);
            $table->json('facts');
            $table->text('manufacturer_text');
            $table->text('description_en');
            $table->text('description_id');
            $table->string('meta_en', 200);
            $table->string('ai_status', 20)->index();
            $table->json('ai_findings');
            $table->json('ai_meta');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->string('rejection_note', 300)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_products');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('activity_logs');
    }
};
