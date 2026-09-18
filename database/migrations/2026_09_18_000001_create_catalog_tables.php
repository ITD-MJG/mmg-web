<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->json('description')->nullable();
            $table->json('certifications')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('sku')->nullable();
            $table->json('name');
            $table->json('short_description')->nullable();
            $table->json('description')->nullable();
            $table->json('specs')->nullable();
            $table->json('certifications')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
            $table->index(['category_id', 'is_published']);
            $table->index(['brand_id', 'is_published']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_cover')->default(false);
            $table->string('path');
            $table->json('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        // Exactly one cover image per product, enforced by the database.
        // MySQL/MariaDB have no partial unique indexes, so a stored generated
        // column collapses the cover row to its product_id and leaves gallery
        // rows NULL. Multiple NULLs are permitted in a unique index, so any
        // number of gallery images can coexist with one cover.
        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedBigInteger('cover_key')
                ->nullable()
                ->storedAs('IF(is_cover, product_id, NULL)');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->unique('cover_key', 'uniq_cover_per_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
