<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Skema testing sqlite tidak kompatibel dengan migration raw-SQL project ini
        // (mis. `ALTER TABLE ... ALTER COLUMN ... DROP NOT NULL` khusus Postgres).
        // Jalankan langsung ke DB pgsql yang sudah bermigrasi penuh.
        // phpunit.xml men-override DB_DATABASE=:memory: secara global (dipakai bareng oleh
        // config sqlite & pgsql), jadi baca ulang .env asli tanpa lewat getenv().
        $env = \Dotenv\Dotenv::parse(file_get_contents(base_path('.env')));

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host'     => $env['DB_HOST'],
            'database.connections.pgsql.port'     => $env['DB_PORT'],
            'database.connections.pgsql.database' => $env['DB_DATABASE'],
            'database.connections.pgsql.username' => $env['DB_USERNAME'],
            'database.connections.pgsql.password' => $env['DB_PASSWORD'],
            'database.connections.pgsql.sslmode'  => $env['DB_SSLMODE'],
        ]);

        // DatabaseTransactions trait mulai transaksi sebelum config di atas berlaku
        // (setUpTraits dijalankan di dalam parent::setUp()), jadi kelola transaksi manual
        // di sini supaya benar-benar membungkus koneksi pgsql yang dipakai test ini.
        DB::connection('pgsql')->beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::connection('pgsql')->rollBack();

        parent::tearDown();
    }

    private function buatUser(): User
    {
        return User::create([
            'name'     => 'Test User ' . Str::random(6),
            'email'    => 'test-' . Str::random(10) . '@example.test',
            'password' => bcrypt('password'),
            'dob'      => '1990-01-01',
        ]);
    }

    public function test_langganan_payment_page_does_not_crash(): void
    {
        $user = $this->buatUser();

        $response = $this->withSession(['user_id' => $user->id])
            ->get(route('payment.konfirmasi', 'langganan'));

        $response->assertOk();
        $response->assertSee('Langganan Bulanan');
    }

    public function test_langganan_bulanan_purchase_creates_pending_order_and_admin_confirm_activates_subscription(): void
    {
        $user = $this->buatUser();

        $this->withSession(['user_id' => $user->id])
            ->post(route('payment.proses', 'langganan-bulanan'), ['bank' => 'bca'])
            ->assertRedirect(route('payment.konfirmasi', 'langganan-bulanan'));

        $order = Order::where('user_id', $user->id)
            ->where('feature_id', Order::SUBSCRIPTION_MONTHLY_FEATURE_ID)
            ->firstOrFail();

        $this->assertSame('pending', $order->status);
        $this->assertSame(config('payment.subscription_monthly_price'), $order->amount);
        $this->assertFalse($user->fresh()->isSubscriber());

        $this->withSession(['admin_logged_in' => true])
            ->post(route('admin.orders.konfirmasi', $order->id))
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->isSubscriber());
        $this->assertSame('monthly', $user->subscriptions()->latest('id')->first()->plan);
        $this->assertEqualsWithDelta(
            now()->addMonth()->timestamp,
            $user->subscriptions()->latest('id')->first()->ends_at->timestamp,
            5
        );
    }

    public function test_langganan_tahunan_purchase_creates_pending_order_and_admin_confirm_activates_subscription(): void
    {
        $user = $this->buatUser();

        $this->withSession(['user_id' => $user->id])
            ->post(route('payment.proses', 'langganan-tahunan'), ['bank' => 'bca'])
            ->assertRedirect(route('payment.konfirmasi', 'langganan-tahunan'));

        $order = Order::where('user_id', $user->id)
            ->where('feature_id', Order::SUBSCRIPTION_YEARLY_FEATURE_ID)
            ->firstOrFail();

        $this->assertSame('pending', $order->status);
        $this->assertSame(config('payment.subscription_yearly_price'), $order->amount);

        $this->withSession(['admin_logged_in' => true])
            ->post(route('admin.orders.konfirmasi', $order->id))
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->isSubscriber());
        $this->assertSame('yearly', $user->subscriptions()->latest('id')->first()->plan);
        $this->assertEqualsWithDelta(
            now()->addMonths(12)->timestamp,
            $user->subscriptions()->latest('id')->first()->ends_at->timestamp,
            5
        );
    }

    public function test_numeric_feature_payment_still_works_with_new_price(): void
    {
        $user = $this->buatUser();

        $response = $this->withSession(['user_id' => $user->id])
            ->get(route('payment.konfirmasi', 1));

        $response->assertOk();
        $response->assertSee('29.000');
    }
}
