<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_reports', function (Blueprint $table) {
            $table->string('provider', 20)->nullable()->after('locale'); // gemini | grok | NULL (laporan lama)
        });
    }

    public function down(): void
    {
        Schema::table('ai_reports', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
