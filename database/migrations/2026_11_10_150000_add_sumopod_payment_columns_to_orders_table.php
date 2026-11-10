<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_id', 64)->nullable()->unique()->after('gateway_order_id');
            $table->text('payment_link_url')->nullable()->after('payment_id');
            $table->timestamp('payment_expires_at')->nullable()->after('payment_link_url');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_id', 'payment_link_url', 'payment_expires_at']);
        });
    }
};
