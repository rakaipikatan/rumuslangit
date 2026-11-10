<?php

namespace Tests\Feature;

use App\Mail\BuktiBayarMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class SumopodPaymentTest extends TestCase
{
    use UsesPostgresTestSchema;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();

        $this->secret = 'whsec_' . base64_encode(random_bytes(24));
        config([
            'services.sumopod_pay.api_key'        => 'test-key',
            'services.sumopod_pay.webhook_secret' => $this->secret,
            'services.sumopod_pay.base_url'       => 'https://api-pay.sumopod.test',
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownPostgresTestSchema();
        parent::tearDown();
    }

    private function buatUser(): User
    {
        return User::create([
            'name' => 'Test ' . Str::random(5), 'email' => Str::random(8) . '@example.test',
            'password' => bcrypt('x'), 'dob' => '1990-01-01',
        ]);
    }

    private function buatOrderQris(User $user, int $featureId = Order::SUBSCRIPTION_MONTHLY_FEATURE_ID, int $amount = 49000): Order
    {
        return Order::create([
            'user_id' => $user->id, 'feature_id' => $featureId, 'amount' => $amount,
            'transfer_amount' => $amount, 'payment_method' => 'qris',
            'gateway_order_id' => 'RL-' . strtoupper(Str::random(12)),
            'payment_id' => (string) Str::uuid(), 'payment_link_url' => 'https://pay.sumopod.test/pay/x',
            'status' => 'pending',
        ]);
    }

    /** Kirim webhook bertanda tangan Svix yang sah. */
    private function kirimWebhook(array $payload, ?string $secret = null, ?int $timestamp = null)
    {
        $body = json_encode($payload);
        $id   = 'msg_' . Str::random(10);
        $ts   = (string) ($timestamp ?? time());
        $key  = base64_decode(str_replace('whsec_', '', $secret ?? $this->secret));
        $sig  = base64_encode(hash_hmac('sha256', "{$id}.{$ts}.{$body}", $key, true));

        return $this->call('POST', route('webhook.sumopod'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_SVIX_ID' => $id, 'HTTP_SVIX_TIMESTAMP' => $ts, 'HTTP_SVIX_SIGNATURE' => 'v1,' . $sig,
        ], $body);
    }

    private function eventSelesai(Order $order, ?int $amount = null): array
    {
        return ['event_type' => 'payment.completed', 'data' => [
            'payment_id' => $order->payment_id, 'order_id' => $order->gateway_order_id,
            'amount' => $amount ?? $order->amount, 'status' => 'completed',
        ]];
    }

    public function test_checkout_qris_membuat_payment_dan_redirect_ke_link_bayar(): void
    {
        Http::fake(['api-pay.sumopod.test/*' => Http::response([
            'payment_id' => 'pay-123', 'order_id' => 'x', 'amount' => 49000,
            'payment_link_url' => 'https://pay.sumopod.test/pay/pay-123',
            'status' => 'pending', 'expires_at' => now()->addDay()->toIso8601String(),
        ], 200)]);

        $user = $this->buatUser();

        $this->withSession(['user_id' => $user->id])
            ->post(route('payment.proses', 'langganan-bulanan'), ['metode' => 'qris'])
            ->assertRedirect('https://pay.sumopod.test/pay/pay-123');

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('qris', $order->payment_method);
        $this->assertSame('pay-123', $order->payment_id);
        $this->assertSame('pending', $order->status);

        Http::assertSent(fn ($r) => $r->hasHeader('X-Api-Key', 'test-key')
            && $r['amount'] === 49000 && $r['currency'] === 'IDR'
            && $r['payment_method_type_code'] === 'qris' && $r['order_id'] === $order->gateway_order_id);
    }

    public function test_submit_qris_dua_kali_tidak_membuat_payment_ganda(): void
    {
        Http::fake(['*' => Http::response([
            'payment_id' => 'pay-1', 'payment_link_url' => 'https://pay.sumopod.test/pay/1', 'expires_at' => now()->addDay()->toIso8601String(),
        ])]);
        $user = $this->buatUser();

        for ($i = 0; $i < 2; $i++) {
            $this->withSession(['user_id' => $user->id])
                ->post(route('payment.proses', 'langganan-bulanan'), ['metode' => 'qris']);
        }

        $this->assertSame(1, Order::where('user_id', $user->id)->count());
        Http::assertSentCount(1);
    }

    public function test_api_sumopod_gagal_membatalkan_order_dan_menampilkan_pesan(): void
    {
        Http::fake(['*' => Http::response(['message' => 'boom'], 500)]);
        $user = $this->buatUser();

        $this->withSession(['user_id' => $user->id])
            ->from(route('payment.konfirmasi', 'langganan-bulanan'))
            ->post(route('payment.proses', 'langganan-bulanan'), ['metode' => 'qris'])
            ->assertSessionHasErrors('metode');

        $this->assertSame('cancel', Order::where('user_id', $user->id)->first()->status);
    }

    public function test_qris_ditolak_bila_api_key_belum_dikonfigurasi(): void
    {
        config(['services.sumopod_pay.api_key' => null]);
        $user = $this->buatUser();

        $this->withSession(['user_id' => $user->id])
            ->post(route('payment.proses', 'langganan-bulanan'), ['metode' => 'qris'])
            ->assertSessionHasErrors('metode');

        $this->assertSame(0, Order::where('user_id', $user->id)->count());
    }

    public function test_webhook_completed_melunasi_order_dan_mengaktifkan_langganan(): void
    {
        $user  = $this->buatUser();
        $order = $this->buatOrderQris($user);

        $this->kirimWebhook($this->eventSelesai($order))->assertOk();

        $this->assertSame('settlement', $order->fresh()->status);
        $this->assertTrue($user->fresh()->isSubscriber());
    }

    public function test_webhook_ganda_idempoten(): void
    {
        $user  = $this->buatUser();
        $order = $this->buatOrderQris($user);

        $this->kirimWebhook($this->eventSelesai($order))->assertOk();
        $this->kirimWebhook($this->eventSelesai($order))->assertOk();

        $this->assertSame(1, $user->subscriptions()->count());
    }

    public function test_webhook_fitur_satuan_membuka_akses_fitur(): void
    {
        $user  = $this->buatUser();
        $order = $this->buatOrderQris($user, 3, 29000);

        $this->kirimWebhook($this->eventSelesai($order))->assertOk();

        $this->assertTrue($user->fresh()->hasAccessToFeature(3));
    }

    public function test_webhook_signature_salah_ditolak(): void
    {
        $user  = $this->buatUser();
        $order = $this->buatOrderQris($user);

        $this->kirimWebhook($this->eventSelesai($order), 'whsec_' . base64_encode('rahasia-lain-yang-salah'))
            ->assertStatus(401);

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_webhook_tanpa_header_signature_ditolak(): void
    {
        $order = $this->buatOrderQris($this->buatUser());

        $this->postJson(route('webhook.sumopod'), $this->eventSelesai($order))->assertStatus(401);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_webhook_timestamp_kedaluwarsa_ditolak(): void
    {
        $order = $this->buatOrderQris($this->buatUser());

        $this->kirimWebhook($this->eventSelesai($order), null, time() - 3600)->assertStatus(401);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_webhook_nominal_tidak_cocok_tidak_melunasi(): void
    {
        $user  = $this->buatUser();
        $order = $this->buatOrderQris($user);

        $this->kirimWebhook($this->eventSelesai($order, 1000))->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertFalse($user->fresh()->isSubscriber());
    }

    public function test_webhook_failed_dan_expired_menutup_order(): void
    {
        $o1 = $this->buatOrderQris($this->buatUser());
        $o2 = $this->buatOrderQris($this->buatUser());

        $this->kirimWebhook(['event_type' => 'payment.failed', 'data' => ['payment_id' => $o1->payment_id, 'order_id' => $o1->gateway_order_id]])->assertOk();
        $this->kirimWebhook(['event_type' => 'payment.expired', 'data' => ['payment_id' => $o2->payment_id, 'order_id' => $o2->gateway_order_id]])->assertOk();

        $this->assertSame('cancel', $o1->fresh()->status);
        $this->assertSame('expire', $o2->fresh()->status);
    }

    public function test_webhook_test_dan_order_tak_dikenal_dibalas_200(): void
    {
        $this->kirimWebhook(['event_type' => 'payment.test', 'data' => []])->assertOk();
        $this->kirimWebhook(['event_type' => 'payment.completed', 'data' => ['payment_id' => 'tidak-ada', 'order_id' => 'RL-TIDAKADA', 'amount' => 1]])->assertOk();
    }

    public function test_komisi_afiliasi_tercatat_saat_qris_lunas(): void
    {
        $aff  = \App\Models\Affiliate::create(['name' => 'A', 'referral_code' => 'T' . strtoupper(Str::random(6)), 'komisi_persen' => 20, 'status' => 'active']);
        $user = $this->buatUser();
        $user->update(['referred_by_affiliate_id' => $aff->id]);
        $order = $this->buatOrderQris($user);

        $this->kirimWebhook($this->eventSelesai($order))->assertOk();

        $this->assertSame(9800, (int) \App\Models\AffiliateCommission::where('order_id', $order->id)->value('komisi_amount'));
    }

    public function test_harga_uji_hanya_berlaku_untuk_email_yang_ditentukan(): void
    {
        Http::fake(['*' => fn () => Http::response([
            'payment_id' => (string) Str::uuid(), 'payment_link_url' => 'https://pay.sumopod.test/pay/uji', 'expires_at' => now()->addDay()->toIso8601String(),
        ])]);

        $penguji = $this->buatUser();
        $lain    = $this->buatUser();
        $penguji->update(['email_verified_at' => now()]);
        $lain->update(['email_verified_at' => now()]);
        config(['payment.test_price' => 500, 'payment.test_email' => strtoupper($penguji->email)]);

        $this->withSession(['user_id' => $penguji->id])->post(route('payment.proses', 3), ['metode' => 'qris']);
        $this->withSession(['user_id' => $lain->id])->post(route('payment.proses', 3), ['metode' => 'qris']);

        $this->assertSame(500, (int) Order::where('user_id', $penguji->id)->value('amount'));
        $this->assertSame(29000, (int) Order::where('user_id', $lain->id)->value('amount'));
    }

    public function test_harga_uji_nonaktif_bila_email_tidak_diisi(): void
    {
        Http::fake(['*' => Http::response([
            'payment_id' => 'p-1', 'payment_link_url' => 'https://pay.sumopod.test/pay/1', 'expires_at' => now()->addDay()->toIso8601String(),
        ])]);

        $user = $this->buatUser();
        $user->update(['email_verified_at' => now()]);
        config(['payment.test_price' => 500, 'payment.test_email' => null]);

        $this->withSession(['user_id' => $user->id])->post(route('payment.proses', 3), ['metode' => 'qris']);

        $this->assertSame(29000, (int) Order::where('user_id', $user->id)->value('amount'));
    }

    public function test_fitur_gratis_tafsir_mimpi_terbuka_tanpa_bayar(): void
    {
        $user = $this->buatUser();
        $user->update(['email_verified_at' => now()]);

        $this->assertTrue($user->hasAccessToFeature(12));   // freemium
        $this->assertFalse($user->hasAccessToFeature(11));  // khusus langganan
        $this->assertFalse($user->hasAccessToFeature(3));   // berbayar

        $this->withSession(['user_id' => $user->id])
            ->get(route('laporan.form', 12))
            ->assertOk()
            ->assertDontSee('Bayar Sekarang');
    }

    public function test_halaman_bayar_fitur_gratis_dialihkan_dan_tak_membuat_order(): void
    {
        $user = $this->buatUser();

        $this->withSession(['user_id' => $user->id])
            ->get(route('payment.konfirmasi', 12))
            ->assertRedirect(route('laporan.form', 12));

        $this->withSession(['user_id' => $user->id])
            ->post(route('payment.proses', 12), ['metode' => 'qris'])
            ->assertStatus(422);

        $this->withSession(['user_id' => $user->id])
            ->get(route('payment.konfirmasi', 11))
            ->assertRedirect(route('trial.katalog'));

        $this->assertSame(0, Order::where('user_id', $user->id)->count());
    }

    public function test_harga_uji_tidak_berlaku_untuk_fitur_gratis(): void
    {
        $user = $this->buatUser();
        $user->update(['email_verified_at' => now()]);
        config(['payment.test_price' => 1000, 'payment.test_email' => $user->email]);

        $this->withSession(['user_id' => $user->id])->post(route('payment.proses', 12), ['metode' => 'qris'])->assertStatus(422);
        $this->assertSame(0, Order::where('user_id', $user->id)->count());
    }

    public function test_checkout_mengirim_return_url_ke_halaman_selesai_dan_halaman_produk(): void
    {
        Http::fake(['*' => Http::response([
            'payment_id' => 'p-r', 'payment_link_url' => 'https://pay.sumopod.test/pay/r', 'expires_at' => now()->addDay()->toIso8601String(),
        ])]);
        $user = $this->buatUser();

        $this->withSession(['user_id' => $user->id])->post(route('payment.proses', 3), ['metode' => 'qris']);

        $order = Order::where('user_id', $user->id)->firstOrFail();
        Http::assertSent(fn ($r) => $r['success_return_url'] === route('payment.selesai', $order->gateway_order_id)
            && $r['cancel_return_url'] === route('payment.konfirmasi', 3));
    }

    public function test_return_url_ditolak_api_diulang_tanpa_override(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['error' => 'return url not allowed'], 422)
            ->push(['payment_id' => 'p-ok', 'payment_link_url' => 'https://pay.sumopod.test/pay/ok', 'expires_at' => now()->addDay()->toIso8601String()], 200)]);
        $user = $this->buatUser();

        $this->withSession(['user_id' => $user->id])
            ->post(route('payment.proses', 3), ['metode' => 'qris'])
            ->assertRedirect('https://pay.sumopod.test/pay/ok');

        Http::assertSentCount(2);
        $this->assertSame('pending', Order::where('user_id', $user->id)->first()->status);
    }

    public function test_selesai_lunas_fitur_langsung_ke_kuesioner_dengan_pesan_sukses(): void
    {
        $user  = $this->buatUser();
        $user->update(['email_verified_at' => now()]);
        $order = $this->buatOrderQris($user, 3, 29000);
        $order->update(['status' => 'settlement', 'settled_at' => now()]);

        $this->withSession(['user_id' => $user->id])
            ->get(route('payment.selesai', $order->gateway_order_id))
            ->assertRedirect(route('laporan.form', 3))
            ->assertSessionHas('success');
    }

    public function test_selesai_lunas_langganan_ke_katalog(): void
    {
        $user  = $this->buatUser();
        $order = $this->buatOrderQris($user);
        $order->update(['status' => 'settlement']);

        $this->withSession(['user_id' => $user->id])
            ->get(route('payment.selesai', $order->gateway_order_id))
            ->assertRedirect(route('trial.katalog'));
    }

    public function test_selesai_pending_menampilkan_halaman_tunggu_dan_json_status(): void
    {
        $user  = $this->buatUser();
        $order = $this->buatOrderQris($user, 3, 29000);

        $this->withSession(['user_id' => $user->id])
            ->get(route('payment.selesai', $order->gateway_order_id))
            ->assertOk()->assertSee('dikonfirmasi');

        $this->withSession(['user_id' => $user->id])
            ->getJson(route('payment.selesai', $order->gateway_order_id))
            ->assertOk()->assertJson(['status' => 'pending', 'redirect' => null]);

        $order->update(['status' => 'settlement']);

        $this->withSession(['user_id' => $user->id])
            ->getJson(route('payment.selesai', $order->gateway_order_id))
            ->assertJson(['status' => 'settlement', 'redirect' => route('payment.selesai', $order->gateway_order_id)]);
    }

    public function test_selesai_gagal_atau_kedaluwarsa_kembali_ke_halaman_bayar(): void
    {
        $user  = $this->buatUser();
        $order = $this->buatOrderQris($user, 3, 29000);
        $order->update(['status' => 'expire']);

        $this->withSession(['user_id' => $user->id])
            ->get(route('payment.selesai', $order->gateway_order_id))
            ->assertRedirect(route('payment.konfirmasi', 3));
    }

    public function test_selesai_tidak_bisa_dibuka_user_lain_atau_tanpa_login(): void
    {
        $pemilik = $this->buatUser();
        $lain    = $this->buatUser();
        $order   = $this->buatOrderQris($pemilik, 3, 29000);

        $this->withSession(['user_id' => $lain->id])->get(route('payment.selesai', $order->gateway_order_id))->assertNotFound();
        $this->flushSession();
        $this->get(route('payment.selesai', $order->gateway_order_id))->assertRedirect(route('trial.index'));
    }

    public function test_katalog_menandai_fitur_yang_sudah_dibeli_bukan_harga(): void
    {
        $user = $this->buatUser();
        $user->update(['email_verified_at' => now()]);
        $order = $this->buatOrderQris($user, 3, 29000);
        $order->update(['status' => 'settlement']);

        $html = $this->withSession(['user_id' => $user->id])->get(route('trial.katalog'))->assertOk()->getContent();

        $this->assertStringContainsString('Sudah dibeli', $html);
        // fitur 3 tidak lagi menampilkan harga; fitur lain yang belum dibeli tetap menampilkannya
        $this->assertSame(1, substr_count($html, 'Sudah dibeli'));
        $this->assertStringContainsString('Rp 29.000', $html);
    }

    public function test_transfer_bank_manual_sudah_dihapus_semua_pembayaran_lewat_qris(): void
    {
        Http::fake(['*' => Http::response([
            'payment_id' => 'p-m', 'payment_link_url' => 'https://pay.sumopod.test/pay/m', 'expires_at' => now()->addDay()->toIso8601String(),
        ])]);
        $user = $this->buatUser();

        // Permintaan lama bergaya transfer manual (field bank saja) tetap berujung QRIS — tak ada order manual.
        $this->withSession(['user_id' => $user->id])
            ->post(route('payment.proses', 3), ['bank' => 'bca', 'metode' => 'manual'])
            ->assertRedirect('https://pay.sumopod.test/pay/m');

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('qris', $order->payment_method);
        $this->assertNull($order->unique_code);
        $this->assertNull($order->bank_tujuan);

        $html = $this->withSession(['user_id' => $this->buatUser()->id])->get(route('payment.konfirmasi', 3))->assertOk()->getContent();
        $this->assertStringNotContainsString('Transfer Bank', $html);
        $this->assertStringNotContainsString('Nomor Rekening', $html);
    }

    public function test_tanpa_konfigurasi_qris_pembayaran_ditolak_dengan_pesan(): void
    {
        config(['services.sumopod_pay.api_key' => null]);
        $user = $this->buatUser();

        $this->withSession(['user_id' => $user->id])
            ->get(route('payment.konfirmasi', 3))
            ->assertOk()->assertSee('QRIS sedang tidak tersedia');
    }

    public function test_bukti_bayar_diantrekan_sekali_saat_lunas_dan_tidak_ganda_pada_webhook_ulang(): void
    {
        Mail::fake();
        $user  = $this->buatUser();
        $order = $this->buatOrderQris($user, 3, 29000);

        $this->kirimWebhook($this->eventSelesai($order))->assertOk();
        $this->kirimWebhook($this->eventSelesai($order))->assertOk();

        Mail::assertQueued(BuktiBayarMail::class, 1);
        Mail::assertQueued(BuktiBayarMail::class, fn ($m) => $m->hasTo($user->email) && $m->order->id === $order->id);
    }

    public function test_bukti_bayar_tidak_dikirim_untuk_pembayaran_gagal_atau_nominal_salah(): void
    {
        Mail::fake();
        $order = $this->buatOrderQris($this->buatUser(), 3, 29000);

        $this->kirimWebhook($this->eventSelesai($order, 1000))->assertOk();
        $this->kirimWebhook(['event_type' => 'payment.failed', 'data' => ['payment_id' => $order->payment_id, 'order_id' => $order->gateway_order_id]])->assertOk();

        Mail::assertNothingQueued();
    }

    public function test_pelunasan_tetap_berhasil_walau_pengguna_tanpa_email(): void
    {
        Mail::fake();
        $user = $this->buatUser();
        $user->forceFill(['email' => null])->save();
        $order = $this->buatOrderQris($user, 3, 29000);

        $this->kirimWebhook($this->eventSelesai($order))->assertOk();

        $this->assertSame('settlement', $order->fresh()->status);
        Mail::assertNothingQueued();
    }

    public function test_isi_bukti_bayar_fitur_satuan(): void
    {
        $user  = $this->buatUser();
        $order = $this->buatOrderQris($user, 3, 29000);
        $order->update(['status' => 'settlement', 'settled_at' => now()]);

        $mail = new BuktiBayarMail($order->fresh());

        $mail->assertSeeInHtml($order->gateway_order_id);
        $mail->assertSeeInHtml('Hari Baik &amp; Momentum Usaha', false);
        $mail->assertSeeInHtml('Rp 29.000');
        $mail->assertSeeInHtml('QRIS');
        $mail->assertSeeInHtml('bukan faktur pajak');
        $mail->assertSeeInHtml(route('laporan.form', 3));
        $mail->assertSeeInText('TOTAL DIBAYAR: Rp 29.000');
        $mail->assertSeeInHtml('folder Spam atau Junk');
        $mail->assertSeeInText('folder Spam atau Junk');
    }

    public function test_isi_bukti_bayar_langganan_memuat_masa_aktif(): void
    {
        $user  = $this->buatUser();
        $order = $this->buatOrderQris($user, Order::SUBSCRIPTION_YEARLY_FEATURE_ID, 350000);
        $this->kirimWebhook($this->eventSelesai($order))->assertOk();

        $mail = new BuktiBayarMail($order->fresh());

        $mail->assertSeeInHtml('Langganan Tahunan');
        $mail->assertSeeInHtml('Rp 350.000');
        $mail->assertSeeInHtml('Aktif sampai');
        $mail->assertSeeInHtml(route('trial.katalog'));
    }
}
