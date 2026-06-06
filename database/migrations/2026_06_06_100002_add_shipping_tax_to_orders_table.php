<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_usd', 12, 2)->default(0)->after('discount_usd');
            $table->decimal('tax_usd', 12, 2)->default(0)->after('shipping_usd');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_usd', 'tax_usd']);
        });
    }
};
