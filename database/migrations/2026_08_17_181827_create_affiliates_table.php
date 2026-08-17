<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('referral_code', 30)->unique();
            $table->string('bank_nama', 30)->nullable();
            $table->string('bank_rekening', 50)->nullable();
            $table->string('bank_atas_nama', 100)->nullable();
            $table->unsignedTinyInteger('komisi_persen')->default(20);
            $table->string('status', 20)->default('active'); // active | inactive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
