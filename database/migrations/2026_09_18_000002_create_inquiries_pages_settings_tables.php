<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->string('locale', 5)->default('id');
            $table->string('status')->default('new');
            // sha256(ip . APP_KEY) — rate limiting only. Raw IPs are never
            // stored, per UU PDP data-minimisation expectations.
            $table->string('ip_hash', 64)->nullable();
            $table->string('source_path')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('ip_hash');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('body')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('inquiries');
    }
};
