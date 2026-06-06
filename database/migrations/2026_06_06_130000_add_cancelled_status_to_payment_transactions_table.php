<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE payment_transactions MODIFY status ENUM('pending', 'paid', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('payment_transactions')->where('status', 'cancelled')->update(['status' => 'failed']);

        DB::statement(
            "ALTER TABLE payment_transactions MODIFY status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending'"
        );
    }
};
