<?php

return [
    // Masa berlaku (jam) link pembayaran QRIS; maksimum SumoPod 24 jam.
    'expire_hours' => env('PAYMENT_EXPIRE_HOURS', 24),

    // Harga langganan (dipakai untuk order feature_id = Order::SUBSCRIPTION_MONTHLY_FEATURE_ID / SUBSCRIPTION_YEARLY_FEATURE_ID).
    'subscription_monthly_price' => env('PAYMENT_LANGGANAN_BULANAN_HARGA', 59000),
    'subscription_yearly_price'  => env('PAYMENT_LANGGANAN_TAHUNAN_HARGA', 499000),

    // Harga UJI sementara, hanya berlaku untuk SATU email (untuk tes transaksi Live bernominal kecil).
    // Aktif bila keduanya terisi di .env; hapus/kosongkan setelah pengujian selesai.
    'test_price' => env('PAYMENT_TEST_PRICE'),
    'test_email' => env('PAYMENT_TEST_EMAIL'),

    // Komisi default (%) untuk afiliator yang disetujui dari halaman pendaftaran.
    'affiliate_default_commission' => env('AFFILIATE_DEFAULT_COMMISSION', 20),

    // Aturan komisi afiliasi (juga ditampilkan sebagai Syarat & Ketentuan ke afiliator).
    'affiliate_commission_months' => env('AFFILIATE_COMMISSION_MONTHS', 12), // komisi hanya untuk pembayaran dalam N bulan sejak pembayaran pertama pelanggan
    'affiliate_hold_days'         => env('AFFILIATE_HOLD_DAYS', 14),         // masa tahan sebelum komisi boleh dibayar

    // Skema harga: pembelian satuan = N kali pembuatan laporan untuk fitur itu (hasil tersimpan selamanya).
    // Setiap pembelian baru menambah N jatah. Langganan = tanpa jatah, dengan batas wajar harian.
    'single_generations'       => env('PAYMENT_JATAH_SATUAN', 3),
    'subscriber_daily_reports' => env('PAYMENT_BATAS_HARIAN_LANGGANAN', 20),
    'free_daily_reports'       => env('PAYMENT_BATAS_HARIAN_GRATIS', 5), // fitur gratis (Tafsir Mimpi) per pengguna

    // Fitur yang HASILNYA berganti tiap hari WIB: jawaban sama di hari yang sama = hasil tersimpan yang sama;
    // di hari berikutnya = laporan baru (tanpa memotong jatah). Nilai: "semua" (default), daftar ID dipisah koma
    // (mis. "3,10,11"), atau kosong = jawaban sama SELALU memakai laporan tersimpan.
    'fitur_harian' => (function () {
        $v = strtolower(trim((string) env('PAYMENT_FITUR_HARIAN', 'semua')));

        return $v === 'semua'
            ? range(1, 12)
            : array_values(array_filter(array_map('trim', explode(',', $v)), 'strlen'));
    })(),

    // Biaya satu email di SumoPod (rupiah) — dipakai catatan riwayat email untuk perkiraan biaya/rekonsiliasi.
    'biaya_email' => env('PAYMENT_BIAYA_EMAIL', 5),
];
