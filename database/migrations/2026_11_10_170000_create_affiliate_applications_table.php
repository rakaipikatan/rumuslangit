<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 255);
            $table->string('phone', 30);
            $table->text('promosi')->nullable();               // rencana/kanal promosi (opsional)
            $table->string('bank_nama', 30);
            $table->string('bank_rekening', 50);
            $table->string('bank_atas_nama', 100);
            $table->string('status', 20)->default('pending');  // pending | approved | rejected
            $table->foreignId('affiliate_id')->nullable()->constrained('affiliates')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_applications');
    }
};
