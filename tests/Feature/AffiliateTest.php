<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AffiliateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Lihat catatan yang sama di SubscriptionPaymentTest: migration project ini
        // pakai raw SQL khusus Postgres, jadi test jalan langsung ke DB pgsql yang
        // sudah bermigrasi, dibungkus transaksi manual supaya tidak mengotori data.
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

        DB::connection('pgsql')->beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::connection('pgsql')->rollBack();

        parent::tearDown();
    }

    private function buatAffiliate(array $overrides = []): Affiliate
    {
        return Affiliate::create(array_merge([
            'name'          => 'Afiliator Test',
            'referral_code' => 'TEST' . strtoupper(Str::random(5)),
            'komisi_persen' => 20,
            'status'        => 'active',
        ], $overrides));
    }

    private function dataPendaftaran(): array
    {
        return [
            'name'              => 'User Referral ' . Str::random(6),
            'jenis_kelamin'     => 'Laki-laki',
            'dob'               => '1995-05-05',
            'birth_hour'        => 10,
            'province'          => 'DKI Jakarta',
            'kota'              => 'Jakarta Selatan',
            'kecamatan'         => 'Kebayoran Baru',
            'kelurahan'         => 'Senayan',
            'anak_ke'           => 1,
            'jumlah_saudara'    => 2,
            'status_pernikahan' => 'Lajang',
        ];
    }

    public function test_referral_link_mengaitkan_user_baru_ke_afiliator(): void
    {
        $affiliate = $this->buatAffiliate();

        // Kunjungan pertama lewat link referral menyimpan afiliator di session.
        $this->get('/?ref=' . $affiliate->referral_code)->assertOk();

        // User daftar (isi data lahir) di sesi yang sama.
        $this->post(route('trial.proses'), $this->dataPendaftaran())
            ->assertRedirect(route('trial.hasil'));

        $user = User::where('name', 'like', 'User Referral%')->latest('id')->first();

        $this->assertNotNull($user);
        $this->assertSame($affiliate->id, $user->referred_by_affiliate_id);
    }

    public function test_kode_referral_tidak_valid_tidak_mengaitkan_apapun(): void
    {
        $this->get('/?ref=KODE-TIDAK-ADA')->assertOk();

        $this->post(route('trial.proses'), $this->dataPendaftaran())
            ->assertRedirect(route('trial.hasil'));

        $user = User::where('name', 'like', 'User Referral%')->latest('id')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->referred_by_affiliate_id);
    }

    public function test_order_settlement_mencatat_komisi_afiliator_dengan_benar(): void
    {
        $affiliate = $this->buatAffiliate(['komisi_persen' => 25]);
        $user = User::create([
            'name' => 'Test User', 'email' => 'aff-' . Str::random(8) . '@example.test',
            'password' => bcrypt('password'), 'dob' => '1990-01-01',
            'referred_by_affiliate_id' => $affiliate->id,
        ]);

        $order = Order::create([
            'user_id' => $user->id, 'feature_id' => 1, 'amount' => 29000,
            'unique_code' => 111, 'transfer_amount' => 29111, 'bank_tujuan' => 'bca',
            'payment_method' => 'manual_transfer',
            'gateway_order_id' => 'MANUAL-' . strtoupper(Str::random(10)),
            'status' => 'pending',
        ]);

        $this->withSession(['admin_logged_in' => true])
            ->post(route('admin.orders.konfirmasi', $order->id))
            ->assertRedirect();

        $komisi = AffiliateCommission::where('order_id', $order->id)->firstOrFail();

        $this->assertSame($affiliate->id, $komisi->affiliate_id);
        $this->assertSame(29000, $komisi->order_amount);
        $this->assertSame(25, $komisi->komisi_persen);
        $this->assertSame(7250, $komisi->komisi_amount); // 25% dari 29000
        $this->assertSame('unpaid', $komisi->status);
    }

    public function test_afiliator_nonaktif_tidak_dapat_komisi(): void
    {
        $affiliate = $this->buatAffiliate(['status' => 'inactive']);
        $user = User::create([
            'name' => 'Test User', 'email' => 'aff-' . Str::random(8) . '@example.test',
            'password' => bcrypt('password'), 'dob' => '1990-01-01',
            'referred_by_affiliate_id' => $affiliate->id,
        ]);

        $order = Order::create([
            'user_id' => $user->id, 'feature_id' => 1, 'amount' => 29000,
            'unique_code' => 222, 'transfer_amount' => 29222, 'bank_tujuan' => 'bca',
            'payment_method' => 'manual_transfer',
            'gateway_order_id' => 'MANUAL-' . strtoupper(Str::random(10)),
            'status' => 'pending',
        ]);

        $this->withSession(['admin_logged_in' => true])
            ->post(route('admin.orders.konfirmasi', $order->id));

        $this->assertDatabaseCount('affiliate_commissions', 0);
    }

    public function test_halaman_admin_affiliates_render_tanpa_error(): void
    {
        $this->buatAffiliate();

        $this->withSession(['admin_logged_in' => true])
            ->get(route('admin.affiliates'))
            ->assertOk();
    }

    public function test_halaman_admin_affiliate_detail_render_tanpa_error(): void
    {
        $affiliate = $this->buatAffiliate();

        $this->withSession(['admin_logged_in' => true])
            ->get(route('admin.affiliates.detail', $affiliate->id))
            ->assertOk()
            ->assertSee($affiliate->referral_code);
    }

    public function test_tandai_lunas_menandai_semua_komisi_unpaid_afiliator(): void
    {
        $affiliate = $this->buatAffiliate();
        $user = User::create([
            'name' => 'Test User', 'email' => 'aff-' . Str::random(8) . '@example.test',
            'password' => bcrypt('password'), 'dob' => '1990-01-01',
            'referred_by_affiliate_id' => $affiliate->id,
        ]);

        foreach ([1, 2] as $featureId) {
            $order = Order::create([
                'user_id' => $user->id, 'feature_id' => $featureId, 'amount' => 29000,
                'unique_code' => 300 + $featureId, 'transfer_amount' => 29000 + 300 + $featureId,
                'bank_tujuan' => 'bca', 'payment_method' => 'manual_transfer',
                'gateway_order_id' => 'MANUAL-' . strtoupper(Str::random(10)),
                'status' => 'pending',
            ]);

            $this->withSession(['admin_logged_in' => true])
                ->post(route('admin.orders.konfirmasi', $order->id));
        }

        $this->assertSame(2, AffiliateCommission::where('affiliate_id', $affiliate->id)->where('status', 'unpaid')->count());

        $this->withSession(['admin_logged_in' => true])
            ->post(route('admin.affiliates.lunas', $affiliate->id))
            ->assertRedirect();

        $this->assertSame(0, AffiliateCommission::where('affiliate_id', $affiliate->id)->where('status', 'unpaid')->count());
        $this->assertSame(2, AffiliateCommission::where('affiliate_id', $affiliate->id)->where('status', 'paid')->count());
    }
}
