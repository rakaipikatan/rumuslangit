<?php

return [
    // Masa berlaku (jam) link pembayaran QRIS; maksimum SumoPod 24 jam.
    'expire_hours' => env('PAYMENT_EXPIRE_HOURS', 24),

    // Harga langganan (dipakai untuk order feature_id = Order::SUBSCRIPTION_MONTHLY_FEATURE_ID / SUBSCRIPTION_YEARLY_FEATURE_ID).
    'subscription_monthly_price' => env('PAYMENT_LANGGANAN_BULANAN_HARGA', 49000),
    'subscription_yearly_price'  => env('PAYMENT_LANGGANAN_TAHUNAN_HARGA', 350000),

    // Harga UJI sementara, hanya berlaku untuk SATU email (untuk tes transaksi Live bernominal kecil).
    // Aktif bila keduanya terisi di .env; hapus/kosongkan setelah pengujian selesai.
    'test_price' => env('PAYMENT_TEST_PRICE'),
    'test_email' => env('PAYMENT_TEST_EMAIL'),

    // Komisi default (%) untuk afiliator yang disetujui dari halaman pendaftaran.
    'affiliate_default_commission' => env('AFFILIATE_DEFAULT_COMMISSION', 20),

    // Aturan komisi afiliasi (juga ditampilkan sebagai Syarat & Ketentuan ke afiliator).
    'affiliate_commission_months' => env('AFFILIATE_COMMISSION_MONTHS', 12), // komisi hanya untuk pembayaran dalam N bulan sejak pembayaran pertama pelanggan
    'affiliate_hold_days'         => env('AFFILIATE_HOLD_DAYS', 14),         // masa tahan sebelum komisi boleh dibayar
];
