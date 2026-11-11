<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class AffiliateTest extends TestCase
{
    use UsesPostgresTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();
    }

    protected function tearDown(): void
    {
        $this->tearDownPostgresTestSchema();
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
            'birth_hour'        => '10:00',
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
            'payment_method' => 'qris',
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
            'payment_method' => 'qris',
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

    public function test_tandai_lunas_hanya_komisi_yang_lewat_masa_tahan(): void
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
                'payment_method' => 'qris', 'gateway_order_id' => 'RL-' . strtoupper(Str::random(10)),
                'status' => 'pending',
            ]);

            $this->withSession(['admin_logged_in' => true])
                ->post(route('admin.orders.konfirmasi', $order->id));
        }

        $this->assertSame(2, AffiliateCommission::where('affiliate_id', $affiliate->id)->where('status', 'unpaid')->count());

        // Satu komisi dibuat 15 hari lalu (lewat masa tahan 14 hari), satu masih baru.
        AffiliateCommission::where('affiliate_id', $affiliate->id)->orderBy('id')->first()
            ->forceFill(['created_at' => now()->subDays(15)])->save();

        $this->withSession(['admin_logged_in' => true])
            ->post(route('admin.affiliates.lunas', $affiliate->id))
            ->assertRedirect();

        $this->assertSame(1, AffiliateCommission::where('affiliate_id', $affiliate->id)->where('status', 'paid')->count());
        $this->assertSame(1, AffiliateCommission::where('affiliate_id', $affiliate->id)->where('status', 'unpaid')->count(), 'yang masih ditahan tidak ikut dilunasi');
    }

    public function test_tidak_ada_komisi_untuk_pembelian_oleh_afiliator_sendiri(): void
    {
        $affiliate = $this->buatAffiliate(['email' => 'sendiri-' . strtolower(Str::random(6)) . '@example.test']);
        $user = User::create([
            'name' => 'Diri Sendiri', 'email' => strtoupper($affiliate->email),
            'password' => bcrypt('password'), 'dob' => '1990-01-01',
            'referred_by_affiliate_id' => $affiliate->id,
        ]);
        $order = Order::create([
            'user_id' => $user->id, 'feature_id' => 1, 'amount' => 29000, 'payment_method' => 'qris',
            'gateway_order_id' => 'RL-' . strtoupper(Str::random(10)), 'status' => 'pending',
        ]);

        $this->withSession(['admin_logged_in' => true])->post(route('admin.orders.konfirmasi', $order->id));

        $this->assertSame('settlement', $order->fresh()->status);
        $this->assertSame(0, AffiliateCommission::where('affiliate_id', $affiliate->id)->count());
    }

    public function test_komisi_hanya_untuk_pembayaran_dalam_12_bulan_sejak_pembayaran_pertama(): void
    {
        $affiliate = $this->buatAffiliate();
        $user = User::create([
            'name' => 'Pelanggan Lama', 'email' => 'lama-' . strtolower(Str::random(6)) . '@example.test',
            'password' => bcrypt('password'), 'dob' => '1990-01-01',
            'referred_by_affiliate_id' => $affiliate->id,
        ]);

        // Pembayaran pertama 13 bulan lalu (sudah lunas, di luar pencatatan komisi saat ini)
        Order::create([
            'user_id' => $user->id, 'feature_id' => 1, 'amount' => 29000, 'payment_method' => 'qris',
            'gateway_order_id' => 'RL-' . strtoupper(Str::random(10)), 'status' => 'settlement',
            'settled_at' => now()->subMonths(13),
        ]);

        $baru = Order::create([
            'user_id' => $user->id, 'feature_id' => 2, 'amount' => 29000, 'payment_method' => 'qris',
            'gateway_order_id' => 'RL-' . strtoupper(Str::random(10)), 'status' => 'pending',
        ]);
        $this->withSession(['admin_logged_in' => true])->post(route('admin.orders.konfirmasi', $baru->id));

        $this->assertSame('settlement', $baru->fresh()->status);
        $this->assertSame(0, AffiliateCommission::where('affiliate_id', $affiliate->id)->count(), 'di luar 12 bulan: tanpa komisi');

        // Pelanggan lain, pembayaran pertama 11 bulan lalu → masih dalam batas
        $user2 = User::create([
            'name' => 'Pelanggan Baru', 'email' => 'baru-' . strtolower(Str::random(6)) . '@example.test',
            'password' => bcrypt('password'), 'dob' => '1990-01-01',
            'referred_by_affiliate_id' => $affiliate->id,
        ]);
        Order::create([
            'user_id' => $user2->id, 'feature_id' => 1, 'amount' => 29000, 'payment_method' => 'qris',
            'gateway_order_id' => 'RL-' . strtoupper(Str::random(10)), 'status' => 'settlement', 'settled_at' => now()->subMonths(11),
        ]);
        $o2 = Order::create([
            'user_id' => $user2->id, 'feature_id' => 2, 'amount' => 29000, 'payment_method' => 'qris',
            'gateway_order_id' => 'RL-' . strtoupper(Str::random(10)), 'status' => 'pending',
        ]);
        $this->withSession(['admin_logged_in' => true])->post(route('admin.orders.konfirmasi', $o2->id));

        $this->assertSame(1, AffiliateCommission::where('affiliate_id', $affiliate->id)->count());
    }
}
