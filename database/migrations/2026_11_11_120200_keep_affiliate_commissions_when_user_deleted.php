<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menghapus pengguna/order TIDAK boleh menghapus catatan komisi afiliator (termasuk yang sudah dibayar):
     * kunci asing diubah dari CASCADE menjadi SET NULL, riwayat nominal tetap utuh.
     */
    public function up(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Tidak dibalik: mengembalikan CASCADE berarti kembali berisiko menghapus riwayat komisi.
    }
};
