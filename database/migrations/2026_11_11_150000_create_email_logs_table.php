<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Riwayat SETIAP email keluar: untuk konsolidasi pemakaian per pengguna/jenis dan rekonsiliasi dengan SumoPod.
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = email uji / penerima bukan pengguna
            $table->string('email', 255)->nullable();                                // penerima
            $table->string('jenis', 30);                                             // otp, login_kode, bukti_bayar, hoki_harian, afiliasi_disetujui, tes_admin, tes_cli, lainnya
            $table->string('status', 10);                                            // terkirim | gagal
            $table->string('pesan_error', 300)->nullable();
            $table->unsignedInteger('biaya')->default(0);                            // rupiah saat dikirim (harga bisa berubah kelak)
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['jenis', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
