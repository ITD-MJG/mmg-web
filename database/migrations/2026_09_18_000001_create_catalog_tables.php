<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The catalogue's category table is `product_categories`, not
        // `categories`. It only ever classifies products, and the explicit name
        // keeps it distinct from the tag vocabulary beside it. Products reach
        // it through `products.category_id` below, which is the product's single
        // primary category.
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('principals', function (Blueprint $table) {
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
            // `category_id` infers `categories`, which no longer exists, so the
            // table is named explicitly.
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->foreignId('principal_id')->nullable()->constrained()->nullOnDelete();
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
            $table->index(['principal_id', 'is_published']);
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

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        // Tags are a free vocabulary a product can carry several of, which is
        // what separates them from `category_id`: a category is where a product
        // files, a tag is what it also happens to be (a model family, a
        // temperature class). The pivot carries only the pair, and the unique
        // index is what makes attaching the same tag twice a no-op rather than
        // a duplicate row.
        Schema::create('product_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'tag_id']);
        });

        // Exactly one cover image per product, enforced by the database.
        // MySQL/MariaDB have no partial unique indexes, so a generated
        // column collapses the cover row to its product_id and leaves gallery
        // rows NULL. Multiple NULLs are permitted in a unique index, so any
        // number of gallery images can coexist with one cover.
        //
        // The column must be VIRTUAL, not STORED. `product_id` is the base
        // column of this generated column and carries an ON DELETE CASCADE
        // foreign key, and MySQL forbids CASCADE, SET NULL, or SET DEFAULT as
        // the ON DELETE/ON UPDATE action for a foreign key on the base column
        // of a STORED generated column. The STORED spelling therefore fails
        // with `ERROR 1215: Cannot add foreign key constraint` against the
        // production MySQL 8.0.46 server. MariaDB accepts it, which is why the
        // bug only surfaced on the deploy target and not in local tests.
        // A unique index on a virtual generated column is still a real,
        // materialised secondary index, so the constraint is enforced on every
        // write. Verified on MySQL 8.0.46 and MariaDB 13.0.2.
        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedBigInteger('cover_key')
                ->nullable()
                ->virtualAs('IF(is_cover, product_id, NULL)');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->unique('cover_key', 'uniq_cover_per_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tags');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('principals');
        Schema::dropIfExists('product_categories');
    }
};
