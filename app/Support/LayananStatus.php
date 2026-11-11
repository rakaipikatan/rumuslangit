<?php

namespace App\Support;

use App\Models\ServiceEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Pencatat kesehatan layanan untuk panel admin "Status layanan".
 * - Keberhasilan: hanya penanda waktu terakhir (cache) — murah, tanpa tulis DB per email/laporan.
 * - Kegagalan: baris di service_events (dihitung 24 jam terakhir) + penanda cache.
 * Tidak boleh PERNAH mengganggu alur utama: semua kegagalan pencatatan ditelan.
 */
class LayananStatus
{
    /** Layanan yang dikenal: kunci => label. */
    public const LAYANAN = [
        'email'      => 'Email (SMTP)',
        'gemini'     => 'AI Gemini',
        'pembayaran' => 'Pembuatan QRIS (SumoPod)',
        'webhook'    => 'Webhook pembayaran SumoPod',
        'hoki'       => 'Hoki Harian (pengiriman pagi)',
        'backup'     => 'Backup database (harian 02:00 WIB)',
    ];

    /** Layanan terjadwal yang dianggap MACET bila keberhasilan terakhir lebih tua dari N jam. */
    public const KEDALUWARSA_JAM = ['backup' => 26];

    public static function ok(string $layanan, ?string $info = null): void
    {
        try {
            Cache::put("layanan:{$layanan}:ok", ['at' => now()->toIso8601String(), 'info' => $info], now()->addDays(30));
        } catch (\Throwable $e) {
            // abaikan
        }
    }

    public static function gagal(string $layanan, string $pesan): void
    {
        try {
            ServiceEvent::create(['layanan' => $layanan, 'ok' => false, 'pesan' => mb_substr($pesan, 0, 300), 'created_at' => now()]);
        } catch (\Throwable $e) {
            // abaikan
        }
    }

    /** Dipanggil saat pekerjaan antrean gagal: email gagal terkirim dicatat sebagai kegagalan layanan email. */
    public static function dariJobGagal(string $namaJob, \Throwable $e): void
    {
        if (str_contains($namaJob, 'SendQueuedMailable')) {
            self::gagal('email', 'Antrean email gagal: ' . $e->getMessage());
            EmailLogger::catatGagal('antrean', null, $e->getMessage());
        }
    }

    /** Detak penjadwal (dipanggil tiap menit oleh schedule:run). */
    public static function detak(): void
    {
        try {
            Cache::put('layanan:scheduler:detak', now()->toIso8601String(), now()->addDay());
        } catch (\Throwable $e) {
            // abaikan
        }
    }

    public static function detakTerakhir(): ?Carbon
    {
        try {
            $v = Cache::get('layanan:scheduler:detak');

            return $v ? Carbon::parse($v) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Ringkasan satu layanan: terakhir sukses, jumlah gagal 24 jam, kegagalan terakhir. */
    public static function ringkas(string $layanan): array
    {
        $ok = null;
        try {
            $v = Cache::get("layanan:{$layanan}:ok");
            $ok = $v ? ['at' => Carbon::parse($v['at']), 'info' => $v['info'] ?? null] : null;
        } catch (\Throwable $e) {
            // abaikan
        }

        $gagal24 = 0;
        $terakhir = null;
        try {
            $gagal24  = ServiceEvent::where('layanan', $layanan)->where('ok', false)->where('created_at', '>=', now()->subDay())->count();
            $terakhir = ServiceEvent::where('layanan', $layanan)->where('ok', false)->latest('id')->first();
        } catch (\Throwable $e) {
            // tabel belum ada / DB bermasalah: tampil "belum ada data"
        }

        // Perlu dicek: ada kegagalan 24 jam terakhir DAN kegagalan terakhir lebih baru daripada sukses terakhir
        $perluCek = $gagal24 > 0 && $terakhir && (!$ok || $terakhir->created_at->gt($ok['at']));

        // Layanan terjadwal yang tak pernah gagal tetapi juga berhenti berhasil (mis. cron mati) → macet
        $catatan = null;
        $batasJam = self::KEDALUWARSA_JAM[$layanan] ?? null;
        if ($batasJam && $ok && $ok['at']->lt(now()->subHours($batasJam))) {
            $perluCek = true;
            $catatan  = "Terakhir berhasil lebih dari {$batasJam} jam lalu — jadwal mungkin macet.";
        }

        return [
            'label'          => self::LAYANAN[$layanan] ?? $layanan,
            'terakhir_ok'    => $ok['at'] ?? null,
            'info_ok'        => $ok['info'] ?? null,
            'gagal_24j'      => $gagal24,
            'gagal_terakhir' => $terakhir,
            'perlu_cek'      => $perluCek,
            'catatan'        => $catatan,
        ];
    }
}
