<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('email_hash', 64)->unique();
            $table->smallInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('trial_used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone_hash']);
            $table->dropColumn('phone_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verifications');
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_hash', 64)->unique()->nullable()->after('id');
        });
    }
};
