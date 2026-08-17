<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('jenis_kelamin', 20)->nullable()->after('name');
            $table->string('kota', 100)->nullable()->after('province');
            $table->string('pekerjaan', 100)->nullable()->after('status_pernikahan');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['jenis_kelamin', 'kota', 'pekerjaan']);
        });
    }
};
