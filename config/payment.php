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
];
