<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('email', 190)->nullable()->after('phone');
            $table->string('product', 190)->nullable()->after('address');
            $table->text('message')->nullable()->after('product');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['email', 'product', 'message']);
        });
    }
};
