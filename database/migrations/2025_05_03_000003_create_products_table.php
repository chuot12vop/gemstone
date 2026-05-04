<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price_usd', 12, 2)->default(0);
            $table->string('image', 255)->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('meta_title', 190)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
