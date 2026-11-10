<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_reports', function (Blueprint $table) {
            // true = pembuatan laporan ini memakai jatah pembelian satuan (bukan langganan/gratis)
            $table->boolean('consumed_quota')->default(false)->after('locale');
            $table->index(['user_id', 'feature_id', 'consumed_quota']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_reports', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'feature_id', 'consumed_quota']);
            $table->dropColumn('consumed_quota');
        });
    }
};
