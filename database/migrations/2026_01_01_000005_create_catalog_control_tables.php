<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_product_types', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('header_image', 120)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('catalog_products', function (Blueprint $table) {
            $table->foreignId('product_type_id')
                ->nullable()
                ->after('category')
                ->constrained('catalog_product_types')
                ->nullOnDelete();
            $table->boolean('product_type_locked')->default(false)->after('product_type_id');
        });

        Schema::create('catalog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('supplier', 40);
            $table->string('full_path', 200);
            $table->timestamps();

            $table->unique(['supplier', 'full_path']);
        });

        Schema::create('catalog_category_type_map', function (Blueprint $table) {
            $table->id();
            $table->string('supplier', 40);
            $table->string('category_path', 200);
            $table->foreignId('product_type_id')
                ->constrained('catalog_product_types')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['supplier', 'category_path']);
        });

        Schema::create('catalog_attribute_groups', function (Blueprint $table) {
            $table->id();
            $table->string('supplier', 40);
            $table->string('label', 80);
            $table->string('unit', 24)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['supplier', 'label']);
        });

        Schema::create('catalog_product_type_key_attributes', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('product_type_id')
                ->constrained('catalog_product_types')
                ->cascadeOnDelete();
            $table->string('label', 80);
            $table->string('unit', 24)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['product_type_id', 'label']);
        });

        Schema::create('catalog_key_attribute_group_map', function (Blueprint $table) {
            $table->foreignId('key_attribute_id')
                ->constrained('catalog_product_type_key_attributes')
                ->cascadeOnDelete();
            $table->foreignId('attribute_group_id')
                ->constrained('catalog_attribute_groups')
                ->cascadeOnDelete();

            $table->primary(['key_attribute_id', 'attribute_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_key_attribute_group_map');
        Schema::dropIfExists('catalog_product_type_key_attributes');
        Schema::dropIfExists('catalog_attribute_groups');
        Schema::dropIfExists('catalog_category_type_map');
        Schema::dropIfExists('catalog_categories');

        Schema::table('catalog_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_type_id');
            $table->dropColumn('product_type_locked');
        });

        Schema::dropIfExists('catalog_product_types');
    }
};
