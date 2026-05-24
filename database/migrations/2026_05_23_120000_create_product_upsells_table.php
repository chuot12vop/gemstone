<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_upsells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('upsell_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('upsale_discount', 5, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'upsell_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_upsells');
    }
};
