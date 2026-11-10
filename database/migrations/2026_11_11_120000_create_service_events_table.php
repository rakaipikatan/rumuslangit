<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catatan kegagalan/kejadian layanan (email, Gemini, webhook, pembayaran, hoki) untuk panel "Status layanan".
        Schema::create('service_events', function (Blueprint $table) {
            $table->id();
            $table->string('layanan', 30);
            $table->boolean('ok')->default(false);
            $table->string('pesan', 300)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['layanan', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_events');
    }
};
