<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('placed_email_sent_at')->nullable()->after('status');
            $table->timestamp('paid_email_sent_at')->nullable()->after('placed_email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['placed_email_sent_at', 'paid_email_sent_at']);
        });
    }
};
