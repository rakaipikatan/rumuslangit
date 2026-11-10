<?php

namespace Tests\Feature;

use App\Data\FiturData;
use App\Jobs\GenerateAIReport;
use App\Models\AiReport;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

/**
 * Skema harga: satuan = 3 jatah pembuatan per pembelian (hasil tersimpan selamanya);
 * langganan = tanpa jatah dengan batas wajar harian; fitur gratis = batas harian per pengguna.
 */
class SkemaHargaTest extends TestCase
{
    use UsesPostgresTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        $this->tearDownPostgresTestSchema();
        parent::tearDown();
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Pembeli', 'email' => strtolower(Str::random(8)) . '@example.test',
            'password' => bcrypt('x'), 'dob' => '1990-01-01', 'birth_hour' => 8,
            'province' => 'DKI JAKARTA', 'kota' => 'Jakarta', 'kecamatan' => 'A', 'kelurahan' => 'B',
            'email_verified_at' => now(),
        ]);
    }

    private function beli(User $u, int $fitur = 3): Order
    {
        return Order::create([
            'user_id' => $u->id, 'feature_id' => $fitur, 'amount' => 29000, 'payment_method' => 'qris',
            'gateway_order_id' => 'RL-' . strtoupper(Str::random(12)), 'status' => 'settlement', 'settled_at' => now(),
        ]);
    }

    private function jawaban(int $fitur, int $variasi = 0): array
    {
        $a = [];
        foreach (range(1, count(FiturData::cari($fitur)['pertanyaan'])) as $i) {
            $a["q{$i}"] = ($i === 1 ? $variasi % 2 : 1);
            if ($i === 2) $a["q{$i}"] = intdiv($variasi, 2) % 2;
            if ($i === 3) $a["q{$i}"] = intdiv($variasi, 4) % 2;
        }

        return $a;
    }

    private function kirim(User $u, int $fitur, int $variasi)
    {
        return $this->withSession(['user_id' => $u->id])->post(route('laporan.proses', $fitur), $this->jawaban($fitur, $variasi));
    }

    private function selesaikanSemua(User $u): void
    {
        AiReport::where('user_id', $u->id)->whereNull('response_text')->update(['response_text' => '{"ok":1}']);
    }

    public function test_satu_pembelian_memberi_tiga_jatah_dan_pembelian_baru_menambahnya(): void
    {
        $u = $this->user();
        $this->assertFalse($u->hasAccessToFeature(3));

        $this->beli($u);
        $this->assertSame(3, $u->kuotaTotal(3));
        $this->assertSame(3, $u->kuotaSisa(3));
        $this->assertTrue($u->hasAccessToFeature(3));

        $this->beli($u);
        $this->assertSame(6, $u->kuotaTotal(3));
        $this->assertSame(0, $u->kuotaTotal(4), 'jatah per fitur, tidak tertukar');
    }

    public function test_tiga_pembuatan_menghabiskan_jatah_lalu_paywall_dengan_pesan_jelas(): void
    {
        $u = $this->user();
        $this->beli($u);

        foreach ([0, 1, 2] as $v) {
            $this->kirim($u, 3, $v)->assertRedirect(route('laporan.status', 3));
            $this->selesaikanSemua($u);
        }

        $this->assertSame(0, $u->kuotaSisa(3));
        $this->assertFalse($u->fresh()->hasAccessToFeature(3));
        Queue::assertPushed(GenerateAIReport::class, 3);

        $this->kirim($u, 3, 3)->assertStatus(403);

        $this->withSession(['user_id' => $u->id])->get(route('laporan.form', 3))
            ->assertOk()
            ->assertSee('Jatah pembuatan laporan ini sudah habis')
            ->assertSee('Lihat laporan terakhir saya');
    }

    public function test_jawaban_identik_menampilkan_laporan_lama_tanpa_memakai_jatah(): void
    {
        $u = $this->user();
        $this->beli($u);

        $this->kirim($u, 3, 0);
        $this->selesaikanSemua($u);
        $this->assertSame(2, $u->kuotaSisa(3));

        $this->kirim($u, 3, 0)->assertRedirect(route('laporan.hasil', 3));

        $this->assertSame(2, $u->kuotaSisa(3));
        $this->assertSame(1, AiReport::where('user_id', $u->id)->where('feature_id', 3)->count());
    }

    public function test_pembuatan_gagal_atau_macet_tidak_menghanguskan_jatah(): void
    {
        $u = $this->user();
        $this->beli($u);

        // Pembuatan lama (20 menit) yang tak pernah selesai
        AiReport::create(['user_id' => $u->id, 'feature_id' => 3, 'locale' => 'id', 'consumed_quota' => true,
            'data_hash' => Str::random(20), 'cached_at' => now()->subMinutes(20)]);
        $this->assertSame(3, $u->kuotaSisa(3));

        // Pembuatan yang baru berjalan tetap dihitung (cegah pengiriman serentak)
        AiReport::create(['user_id' => $u->id, 'feature_id' => 3, 'locale' => 'id', 'consumed_quota' => true,
            'data_hash' => Str::random(20), 'cached_at' => now()]);
        $this->assertSame(2, $u->kuotaSisa(3));
    }

    public function test_beli_lagi_dimungkinkan_saat_jatah_habis(): void
    {
        $u = $this->user();
        $this->beli($u);
        foreach ([0, 1, 2] as $v) { $this->kirim($u, 3, $v); $this->selesaikanSemua($u); }

        $this->withSession(['user_id' => $u->id])->get(route('payment.konfirmasi', 3))->assertOk();
    }

    public function test_laporan_terakhir_bisa_dibuka_tanpa_akses_dan_hanya_milik_sendiri(): void
    {
        $u = $this->user();
        $lain = $this->user();
        $r = AiReport::create(['user_id' => $u->id, 'feature_id' => 3, 'locale' => 'id', 'data_hash' => Str::random(20),
            'response_text' => 'isi laporan', 'cached_at' => now()]);

        $this->assertFalse($u->hasAccessToFeature(3));
        $this->withSession(['user_id' => $u->id])->get(route('laporan.terakhir', 3))
            ->assertRedirect(route('laporan.hasil', 3))->assertSessionHas('report_id_3', $r->id);

        $this->flushSession();
        $this->withSession(['user_id' => $lain->id])->get(route('laporan.terakhir', 3))
            ->assertRedirect(route('laporan.form', 3));
    }

    public function test_form_menampilkan_sisa_jatah_untuk_pembeli_satuan(): void
    {
        $u = $this->user();
        $this->beli($u);
        $this->kirim($u, 3, 0);
        $this->selesaikanSemua($u);

        $this->withSession(['user_id' => $u->id])->get(route('laporan.form', 3))
            ->assertOk()->assertSee('Sisa jatah: 2 dari 3');
    }

    public function test_langganan_tidak_memakai_jatah_tetapi_dibatasi_per_hari(): void
    {
        config(['payment.subscriber_daily_reports' => 2]);
        $u = $this->user();
        Subscription::create(['user_id' => $u->id, 'plan' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(), 'auto_renew' => false]);
        $u->update(['subscription_status' => 'active']);

        $this->kirim($u, 3, 0)->assertRedirect(route('laporan.status', 3));
        $this->kirim($u, 4, 0)->assertRedirect(route('laporan.status', 4));
        $this->assertSame(0, AiReport::where('user_id', $u->id)->where('consumed_quota', true)->count());

        $this->kirim($u, 6, 0)->assertSessionHasErrors('batas');
        $this->assertSame(2, AiReport::where('user_id', $u->id)->count());
    }

    public function test_fitur_gratis_dibatasi_per_hari_per_pengguna(): void
    {
        config(['payment.free_daily_reports' => 1]);
        $u = $this->user();
        AiReport::create(['user_id' => $u->id, 'feature_id' => 12, 'locale' => 'id', 'data_hash' => Str::random(20),
            'response_text' => 'tafsir', 'cached_at' => now()]);

        $this->withSession(['user_id' => $u->id])
            ->post(route('laporan.proses', 12), ['mimpi' => 'Bermimpi terbang di atas laut'])
            ->assertSessionHasErrors('batas');
    }

    public function test_harga_dan_teks_skema_baru(): void
    {
        $this->assertSame(59000, (int) config('payment.subscription_monthly_price'));
        $this->assertSame(499000, (int) config('payment.subscription_yearly_price'));
        $this->assertSame(3, (int) config('payment.single_generations'));

        $u = $this->user();
        $this->withSession(['user_id' => $u->id])->get(route('laporan.form', 3))
            ->assertOk()->assertSee('3 jatah laporan · hasil tersimpan selamanya')->assertSee('Apa itu jatah?')->assertSee('Hemat 30%')->assertDontSee('Akses selamanya');

        $this->withSession(['user_id' => $u->id])->get(route('trial.katalog'))
            ->assertOk()->assertSee('Rp 499.000/tahun');

        $this->withSession(['user_id' => $u->id])->get(route('payment.konfirmasi', 'langganan-tahunan'))
            ->assertOk()->assertSee('per tahun')->assertSee('Akses semua laporan selama masa langganan');
    }

    public function test_jawaban_sama_di_hari_yang_sama_dipakai_ulang_besok_dibuat_baru_tanpa_memakai_jatah(): void
    {
        config(['payment.fitur_harian' => [3]]); // mekanisme harian dinyalakan untuk fitur 3
        $u = $this->user();
        $this->beli($u);

        // 10 Nov 17:00 WIB (10:00 UTC): laporan pertama
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 10:00:00', 'UTC'));
        $this->kirim($u, 3, 0)->assertRedirect(route('laporan.status', 3));
        $this->selesaikanSemua($u);
        $this->assertSame(2, $u->kuotaSisa(3));

        // 10 Nov 23:30 WIB (16:30 UTC): masih hari yang sama di WIB → laporan lama, jatah tak berkurang
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 16:30:00', 'UTC'));
        $this->kirim($u, 3, 0)->assertRedirect(route('laporan.hasil', 3));
        $this->assertSame(1, AiReport::where('user_id', $u->id)->where('feature_id', 3)->count());
        $this->assertSame(2, $u->kuotaSisa(3));

        // 11 Nov 00:05 WIB (17:05 UTC): sudah hari berikutnya di WIB → laporan BARU dibuat,
        // tetapi jatah TIDAK berkurang karena jawabannya sama dengan yang pernah dibuat
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 17:05:00', 'UTC'));
        $this->kirim($u, 3, 0)->assertRedirect(route('laporan.status', 3));
        $this->assertSame(2, AiReport::where('user_id', $u->id)->where('feature_id', 3)->count());
        $this->assertSame(2, $u->kuotaSisa(3));
        Queue::assertPushed(GenerateAIReport::class, 2);

        // Setelah laporan hari baru selesai, pengiriman ulang di hari itu memakai laporan hari itu
        $this->selesaikanSemua($u);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-11 09:00:00', 'UTC'));
        $this->kirim($u, 3, 0)->assertRedirect(route('laporan.hasil', 3));
        $this->assertSame(2, AiReport::where('user_id', $u->id)->where('feature_id', 3)->count());
    }

    public function test_batas_harian_berganti_tengah_malam_wib_bukan_utc(): void
    {
        config(['payment.free_daily_reports' => 1]);
        $u = $this->user();

        // 10 Nov 23:50 WIB (16:50 UTC) sudah 1 pembuatan
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 16:50:00', 'UTC'));
        AiReport::create(['user_id' => $u->id, 'feature_id' => 12, 'locale' => 'id', 'data_hash' => Str::random(20),
            'response_text' => 'tafsir', 'cached_at' => now()]);

        // 10 Nov 23:59 WIB masih hari yang sama → ditolak
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 16:59:00', 'UTC'));
        $this->withSession(['user_id' => $u->id])->post(route('laporan.proses', 12), ['mimpi' => 'Mimpi pertama malam ini'])
            ->assertSessionHasErrors('batas');

        // 11 Nov 00:01 WIB (17:01 UTC) hari baru → boleh lagi
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 17:01:00', 'UTC'));
        $this->withSession(['user_id' => $u->id])->post(route('laporan.proses', 12), ['mimpi' => 'Mimpi pertama malam ini'])
            ->assertSessionHasNoErrors();
    }

    public function test_jatah_hanya_terpakai_untuk_jawaban_yang_berbeda(): void
    {
        config(['payment.fitur_harian' => [3]]); // agar tiap hari membuat laporan baru (bukti jatah tak terpotong)
        $u = $this->user();
        $this->beli($u);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 03:00:00', 'UTC'));

        // Jawaban A di hari 1 → memakai 1 jatah
        $this->kirim($u, 3, 0); $this->selesaikanSemua($u);
        $this->assertSame(2, $u->kuotaSisa(3));

        // Hari 2-5, jawaban A yang sama berulang kali → laporan baru tiap hari, jatah tetap 2
        foreach ([1, 2, 3, 4] as $hari) {
            $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 03:00:00', 'UTC')->addDays($hari));
            $this->kirim($u, 3, 0)->assertRedirect(route('laporan.status', 3));
            $this->selesaikanSemua($u);
            $this->assertSame(2, $u->kuotaSisa(3), "hari ke-{$hari}: jawaban sama tidak boleh memakai jatah");
        }
        $this->assertSame(5, AiReport::where('user_id', $u->id)->where('feature_id', 3)->count());

        // Jawaban B (berbeda) → memakai 1 jatah
        $this->kirim($u, 3, 1); $this->selesaikanSemua($u);
        $this->assertSame(1, $u->kuotaSisa(3));

        // Jawaban C (berbeda) → jatah terakhir
        $this->kirim($u, 3, 2); $this->selesaikanSemua($u);
        $this->assertSame(0, $u->kuotaSisa(3));
        $this->assertFalse($u->fresh()->hasAccessToFeature(3));
    }

    public function test_ganti_bahasa_dengan_jawaban_sama_tidak_memakai_jatah(): void
    {
        $u = $this->user();
        $this->beli($u);

        $this->kirim($u, 3, 0); $this->selesaikanSemua($u);
        $this->assertSame(2, $u->kuotaSisa(3));

        app()->setLocale('en');
        $this->withSession(['user_id' => $u->id, 'locale' => 'en'])->post(route('laporan.proses', 3), $this->jawaban(3, 0));
        $this->selesaikanSemua($u);

        $this->assertSame(2, AiReport::where('user_id', $u->id)->where('feature_id', 3)->count());
        $this->assertSame(2, $u->kuotaSisa(3));
    }

    public function test_pembuatan_gagal_lalu_diulang_dengan_jawaban_sama_tetap_memakai_jatah_sekali(): void
    {
        $u = $this->user();
        $this->beli($u);

        // Percobaan pertama macet 20 menit (tak pernah selesai, tak dihitung)
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 03:00:00', 'UTC'));
        $this->kirim($u, 3, 0);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 03:20:00', 'UTC'));
        $this->assertSame(3, $u->kuotaSisa(3));

        // Diulang dengan jawaban sama → baru benar-benar memakai jatah (karena belum pernah berhasil)
        $this->kirim($u, 3, 0); $this->selesaikanSemua($u);
        $this->assertSame(2, $u->kuotaSisa(3));
    }

    public function test_bila_diatur_kosong_jawaban_sama_selalu_memakai_laporan_tersimpan_termasuk_3_10_11(): void
    {
        config(['payment.fitur_harian' => []]); // PAYMENT_FITUR_HARIAN= (kosong)
        $u = $this->user();
        $this->beli($u, 3);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 03:00:00', 'UTC'));
        $this->kirim($u, 3, 0); $this->selesaikanSemua($u);

        foreach ([1, 30] as $hari) {
            $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 03:00:00', 'UTC')->addDays($hari));
            $this->kirim($u, 3, 0)->assertRedirect(route('laporan.hasil', 3));
        }

        Queue::assertPushed(GenerateAIReport::class, 1);
        $this->assertSame(2, $u->kuotaSisa(3));
    }

    public function test_fitur_di_luar_daftar_harian_jawaban_sama_selalu_memakai_laporan_tersimpan_tanpa_ai(): void
    {
        config(['payment.fitur_harian' => [3]]); // fitur 1 tidak harian
        $u = $this->user();
        $this->beli($u, 1); // Arah Mata Angin Rezeki — tidak bergantung tanggal

        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 03:00:00', 'UTC'));
        $this->kirim($u, 1, 0)->assertRedirect(route('laporan.status', 1));
        $this->selesaikanSemua($u);
        Queue::assertPushed(GenerateAIReport::class, 1);

        // 1 hari, 1 pekan, 1 bulan kemudian: jawaban sama → tampilkan yang tersimpan, TANPA job AI baru
        foreach ([1, 7, 30] as $hari) {
            $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 03:00:00', 'UTC')->addDays($hari));
            $this->kirim($u, 1, 0)->assertRedirect(route('laporan.hasil', 1));
        }

        Queue::assertPushed(GenerateAIReport::class, 1);
        $this->assertSame(1, AiReport::where('user_id', $u->id)->where('feature_id', 1)->count());
        $this->assertSame(2, $u->kuotaSisa(1));
    }

    public function test_fitur_bergantung_tanggal_dibuat_baru_tiap_hari_tanpa_memotong_jatah(): void
    {
        // Default: SEMUA fitur berganti harian (hari sama = hasil sama, hari baru = laporan baru)
        foreach (range(1, 12) as $id) {
            $this->assertTrue(!empty(FiturData::cari($id)['harian']), "fitur {$id}");
        }

        // PAYMENT_FITUR_HARIAN dapat membatasi daftar
        config(['payment.fitur_harian' => [3, 11]]);
        $this->assertTrue(!empty(FiturData::cari(3)['harian']));
        $this->assertTrue(!empty(FiturData::cari(11)['harian']));
        $this->assertTrue(empty(FiturData::cari(1)['harian']));
        $this->assertTrue(empty(FiturData::cari(10)['harian']));

        $u = $this->user();
        $this->beli($u, 3);

        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 03:00:00', 'UTC'));
        $this->kirim($u, 3, 0); $this->selesaikanSemua($u);
        $this->kirim($u, 3, 0)->assertRedirect(route('laporan.hasil', 3)); // hari sama → tersimpan

        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-11 03:00:00', 'UTC'));
        $this->kirim($u, 3, 0)->assertRedirect(route('laporan.status', 3));  // hari baru → dibuat baru
        $this->assertSame(2, $u->kuotaSisa(3), 'jawaban sama tidak memotong jatah');
        Queue::assertPushed(GenerateAIReport::class, 2);
    }

    public function test_default_semua_fitur_hari_sama_hasil_sama_hari_baru_laporan_baru_jatah_tetap(): void
    {
        $u = $this->user();
        $this->beli($u, 1); // fitur biasa, tanpa konfigurasi khusus

        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 03:00:00', 'UTC'));
        $this->kirim($u, 1, 0); $this->selesaikanSemua($u);
        $pertama = AiReport::where('user_id', $u->id)->where('feature_id', 1)->value('id');

        // berkali-kali di hari yang sama → hasil yang sama persis (laporan yang sama)
        foreach ([1, 5, 9] as $jam) {
            $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-10 03:00:00', 'UTC')->addHours($jam));
            $this->kirim($u, 1, 0)->assertRedirect(route('laporan.hasil', 1));
            $this->assertSame($pertama, session('report_id_1'));
        }
        Queue::assertPushed(GenerateAIReport::class, 1);

        // hari berikutnya → laporan baru, jatah tetap
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-11 03:00:00', 'UTC'));
        $this->kirim($u, 1, 0)->assertRedirect(route('laporan.status', 1));
        Queue::assertPushed(GenerateAIReport::class, 2);
        $this->assertSame(2, $u->kuotaSisa(1));
    }
}
