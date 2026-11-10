<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;
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
}
