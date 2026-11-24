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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('number', 30)->unique();
            $table->string('name', 80);
            $table->string('company', 120)->nullable();
            $table->string('email', 120);
            $table->string('phone', 30)->nullable();
            $table->string('city', 80)->nullable();
            $table->text('message')->nullable();
            $table->string('source', 20)->default('web');
            $table->string('status', 20)->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('contact_id')->nullable()->constrained();
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('quotation_id')->nullable()->unique()->constrained();
            $table->timestamps();
        });

        Schema::create('inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_id')->nullable()->constrained();
            $table->string('title', 200);
            $table->string('part_number', 40)->nullable();
            $table->unsignedInteger('quantity');
            $table->string('unit', 10)->nullable();
            $table->string('note', 200)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_items');
        Schema::dropIfExists('inquiries');
    }
};
