<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->smallInteger('unique_code')->nullable()->after('amount');
            $table->integer('transfer_amount')->nullable()->after('unique_code');
            $table->string('bank_tujuan', 30)->nullable()->after('transfer_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['unique_code', 'transfer_amount', 'bank_tujuan']);
        });
    }
};
