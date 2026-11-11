<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->integer('jumlah');
            $table->string('status', 20)->default('pending'); // pending | paid
            // Salinan rekening saat permintaan dibuat (tetap benar walau rekening diubah belakangan)
            $table->string('bank_nama', 30)->nullable();
            $table->string('bank_rekening', 50)->nullable();
            $table->string('bank_atas_nama', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->foreignId('withdrawal_id')->nullable()->after('status')->constrained('affiliate_withdrawals')->nullOnDelete();
        });

        Schema::create('bantuan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('affiliate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pertanyaan', 600);
            $table->text('jawaban')->nullable();
            $table->string('sumber', 10); // ai | faq | gagal
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bantuan_logs');
        Schema::table('affiliate_commissions', fn (Blueprint $t) => $t->dropConstrainedForeignId('withdrawal_id'));
        Schema::dropIfExists('affiliate_withdrawals');
    }
};
