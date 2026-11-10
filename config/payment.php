<?php

return [
    // Rekening tujuan transfer manual. Nama & nomor WAJIB diisi lewat .env (PAYMENT_*), tanpa default di repo. "kode" dipakai sebagai value <option> di form.
    'bank_accounts' => [
        [
            'kode'   => 'bca',
            'bank'   => 'BCA',
            'nama'   => env('PAYMENT_BCA_NAMA'),
            'nomor'  => env('PAYMENT_BCA_NOMOR'),
        ],
        [
            'kode'   => 'cimb',
            'bank'   => 'CIMB Niaga',
            'nama'   => env('PAYMENT_CIMB_NAMA'),
            'nomor'  => env('PAYMENT_CIMB_NOMOR'),
        ],
    ],

    // Batas waktu (jam) sebelum order manual transfer yang belum dikonfirmasi dianggap expired.
    'expire_hours' => env('PAYMENT_EXPIRE_HOURS', 24),

    // Harga langganan (dipakai untuk order feature_id = Order::SUBSCRIPTION_MONTHLY_FEATURE_ID / SUBSCRIPTION_YEARLY_FEATURE_ID).
    'subscription_monthly_price' => env('PAYMENT_LANGGANAN_BULANAN_HARGA', 49000),
    'subscription_yearly_price'  => env('PAYMENT_LANGGANAN_TAHUNAN_HARGA', 350000),
];
