<?php

namespace Tests\Feature;

use App\Models\BantuanLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class BantuanChatTest extends TestCase
{
    use UsesPostgresTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();
        // Tanpa AI sungguhan di tes
        config(['services.xai.key' => null, 'services.xai.model' => null, 'services.gemini.key' => null]);
    }

    protected function tearDown(): void
    {
        $this->tearDownPostgresTestSchema();
        parent::tearDown();
    }

    public function test_tanpa_ai_menjawab_dari_faq_berdasar_kata_kunci_dan_mencatat_log(): void
    {
        $r = $this->postJson(route('bantuan.chat'), ['pesan' => 'Saya sudah bayar QRIS tapi belum masuk'])->assertOk()->json();

        $this->assertSame('faq', $r['sumber']);
        $this->assertStringContainsString('QRIS', $r['jawaban']);
        $this->assertSame(1, BantuanLog::where('sumber', 'faq')->count());
    }

    public function test_pertanyaan_tak_dikenal_mendapat_langkah_umum_dan_ditandai_gagal(): void
    {
        $r = $this->postJson(route('bantuan.chat'), ['pesan' => 'zzz qqq xyz'])->assertOk()->json();

        $this->assertSame('gagal', $r['sumber']);
        $this->assertStringContainsString('Spam', $r['jawaban']);
        $this->assertSame(1, BantuanLog::where('sumber', 'gagal')->count());
    }

    public function test_ai_dipakai_bila_tersedia_dan_menerima_data_akun_pengguna_sendiri(): void
    {
        config(['services.xai.key' => 'k', 'services.xai.model' => 'm', 'services.xai.base_url' => 'https://ai.test/v1']);
        Http::fake(['ai.test/*' => Http::response(['choices' => [['message' => ['content' => '{"jawaban":"Pesanan Anda sudah lunas."}']]]])]);

        $u = User::create(['name' => 'Siti Aminah', 'email' => 's' . Str::random(5) . '@example.test', 'dob' => '1990-01-01', 'birth_hour' => 1, 'email_verified_at' => now()]);
        $lain = User::create(['name' => 'Rahasia Orang', 'email' => 'r' . Str::random(5) . '@example.test', 'dob' => '1990-01-01', 'birth_hour' => 1, 'email_verified_at' => now()]);
        Order::create(['user_id' => $u->id, 'feature_id' => -1, 'amount' => 59000, 'status' => 'settlement', 'payment_method' => 'qris', 'gateway_order_id' => 'RL-MILIKSAYA']);
        Order::create(['user_id' => $lain->id, 'feature_id' => 1, 'amount' => 29000, 'status' => 'pending', 'payment_method' => 'qris', 'gateway_order_id' => 'RL-MILIKORANGLAIN']);

        $r = $this->withSession(['user_id' => $u->id])->postJson(route('bantuan.chat'), ['pesan' => 'Apakah pembayaran saya berhasil?'])->assertOk()->json();

        $this->assertSame('ai', $r['sumber']);
        $this->assertSame('Pesanan Anda sudah lunas.', $r['jawaban']);

        Http::assertSent(function ($req) {
            $isi = json_encode($req->data());
            return str_contains($isi, 'RL-MILIKSAYA') && !str_contains($isi, 'RL-MILIKORANGLAIN') && !str_contains($isi, 'Rahasia Orang');
        });
        $this->assertSame($u->id, BantuanLog::first()->user_id);
    }

    public function test_ai_gagal_jatuh_ke_faq(): void
    {
        config(['services.xai.key' => 'k', 'services.xai.model' => 'm', 'services.xai.base_url' => 'https://ai.test/v1']);
        Http::fake(['ai.test/*' => Http::response('boom', 500)]);

        $r = $this->postJson(route('bantuan.chat'), ['pesan' => 'cara login tanpa password'])->assertOk()->json();

        $this->assertSame('faq', $r['sumber']);
    }

    public function test_validasi_dan_batas_harian(): void
    {
        $this->postJson(route('bantuan.chat'), ['pesan' => 'a'])->assertStatus(422);
        $this->postJson(route('bantuan.chat'), ['pesan' => str_repeat('x', 501)])->assertStatus(422);

        RateLimiter::clear('bantuan-harian:ip127.0.0.1');
        for ($i = 0; $i < 60; $i++) {
            RateLimiter::hit('bantuan-harian:ip127.0.0.1', 86400);
        }
        $this->postJson(route('bantuan.chat'), ['pesan' => 'halo ada yang bisa bantu'])->assertStatus(429)->assertJson(['sumber' => 'batas']);
    }

    public function test_widget_tampil_di_halaman_dan_admin_bisa_melihat_log(): void
    {
        $this->get('/')->assertOk()->assertSee('bantuanChat', false);

        BantuanLog::create(['pertanyaan' => 'pertanyaan unik 123', 'jawaban' => 'j', 'sumber' => 'gagal', 'created_at' => now()]);
        $this->withSession(['admin_logged_in' => true])->get(route('admin.bantuan', ['sumber' => 'gagal']))
            ->assertOk()->assertSee('pertanyaan unik 123');
    }
}
