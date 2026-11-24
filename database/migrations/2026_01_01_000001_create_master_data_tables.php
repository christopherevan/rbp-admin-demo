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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('code', 8)->unique();
            $table->string('name', 120);
            $table->string('address_line1', 160)->nullable();
            $table->string('address_line2', 160)->nullable();
            $table->string('channel', 20)->default('direct');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('salutation', 4)->default('mr');
            $table->string('name', 80);
            $table->string('phone', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('code', 10)->unique();
            $table->string('name', 40);
            $table->timestamps();
        });

        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('part_number', 40)->unique();
            $table->string('description', 200);
            $table->foreignId('uom_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('list_price');
            $table->boolean('stocked')->default(true);
            $table->integer('stock_qty')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
        Schema::dropIfExists('uoms');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('customers');
    }
};
