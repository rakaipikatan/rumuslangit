<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_hash', 64)->unique()->nullable()->after('id');
            $table->date('dob')->nullable()->after('email');
            $table->smallInteger('birth_hour')->default(0)->after('dob');
            $table->string('province', 100)->nullable()->after('birth_hour');
            $table->string('kecamatan', 100)->nullable()->after('province');
            $table->string('kelurahan', 100)->nullable()->after('kecamatan');
            $table->string('subscription_status', 20)->default('free')->after('kelurahan');
        });

        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone_hash', 64);
            $table->string('otp_code', 6)->nullable();
            $table->smallInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('trial_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('birth_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->smallInteger('weton_neptune')->nullable();
            $table->string('shio', 20)->nullable();
            $table->string('zodiac_sign', 20)->nullable();
            $table->string('ascendant', 20)->nullable();
            $table->string('element', 20)->nullable();
            $table->timestamp('calculated_at')->useCurrent();
        });

        Schema::create('questionnaire_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->smallInteger('feature_id');
            $table->jsonb('answers');
            $table->timestamps();
        });

        Schema::create('ai_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->smallInteger('feature_id');
            $table->string('data_hash', 64);
            $table->text('prompt_used')->nullable();
            $table->text('response_text')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamp('cached_at')->useCurrent();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->smallInteger('feature_id')->nullable();
            $table->smallInteger('package_id')->nullable();
            $table->integer('amount');
            $table->string('payment_method', 50)->nullable();
            $table->string('gateway_order_id', 100)->unique()->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('plan', 10);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('auto_renew')->default(true);
            $table->string('gateway_token', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('pdf_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('report_id')->references('id')->on('ai_reports');
            $table->string('file_path', 255)->nullable();
            $table->text('signed_url')->nullable();
            $table->timestamp('url_expires_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_downloads');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('ai_reports');
        Schema::dropIfExists('questionnaire_sessions');
        Schema::dropIfExists('birth_profiles');
        Schema::dropIfExists('phone_verifications');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone_hash', 'dob', 'birth_hour',
                'province', 'kecamatan', 'kelurahan', 'subscription_status'
            ]);
        });
    }
};