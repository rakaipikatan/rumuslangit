<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('phone_verifications');
    }

    public function down(): void
    {
        Schema::create('phone_verifications', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('phone_hash', 64);
            $table->string('otp_code', 6)->nullable();
            $table->smallInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('trial_used_at')->nullable();
            $table->timestamps();
        });
    }
};
