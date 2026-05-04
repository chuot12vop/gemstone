<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->string('customer_email', 190);
            $table->string('customer_name', 160);
            $table->text('shipping_address');
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('subtotal_usd', 12, 2);
            $table->decimal('total_display', 12, 2);
            $table->enum('status', ['pending', 'paid', 'shipped', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
