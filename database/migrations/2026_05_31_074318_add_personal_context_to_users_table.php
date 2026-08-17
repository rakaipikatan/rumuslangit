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
        Schema::table('users', function (Blueprint $table) {
            $table->string('agama', 50)->nullable()->after('kelurahan');
            $table->smallInteger('anak_ke')->nullable()->after('agama');
            $table->smallInteger('jumlah_saudara')->nullable()->after('anak_ke');
            $table->string('status_pernikahan', 20)->nullable()->after('jumlah_saudara');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['agama', 'anak_ke', 'jumlah_saudara', 'status_pernikahan']);
        });
    }
};
