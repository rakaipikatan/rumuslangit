<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wilayah_provinsi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
        });

        Schema::create('wilayah_kota', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->foreignId('provinsi_id')->constrained('wilayah_provinsi')->onDelete('cascade');
            $table->string('nama', 100);
            $table->string('tipe', 20)->default('Kabupaten'); // Kabupaten | Kota
        });

        Schema::create('wilayah_kecamatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->foreignId('kota_id')->constrained('wilayah_kota')->onDelete('cascade');
            $table->string('nama', 100);
        });

        Schema::create('wilayah_kelurahan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 15)->unique();
            $table->foreignId('kecamatan_id')->constrained('wilayah_kecamatan')->onDelete('cascade');
            $table->string('nama', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah_kelurahan');
        Schema::dropIfExists('wilayah_kecamatan');
        Schema::dropIfExists('wilayah_kota');
        Schema::dropIfExists('wilayah_provinsi');
    }
};
