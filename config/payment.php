<?php

return [
    // Rekening tujuan transfer manual. "kode" dipakai sebagai value <option> di form.
    'bank_accounts' => [
        [
            'kode'   => 'bca',
            'bank'   => 'BCA',
            'nama'   => env('PAYMENT_BCA_NAMA', 'Yohanes Sefrianto'),
            'nomor'  => env('PAYMENT_BCA_NOMOR', '6630676183'),
        ],
        [
            'kode'   => 'cimb',
            'bank'   => 'CIMB Niaga',
            'nama'   => env('PAYMENT_CIMB_NAMA', 'Yohanes Sefrianto'),
            'nomor'  => env('PAYMENT_CIMB_NOMOR', '0702731304800'),
        ],
    ],

    // Batas waktu (jam) sebelum order manual transfer yang belum dikonfirmasi dianggap expired.
    'expire_hours' => env('PAYMENT_EXPIRE_HOURS', 24),

    // Harga langganan bulanan (dipakai untuk order feature_id = Order::SUBSCRIPTION_FEATURE_ID).
    'subscription_monthly_price' => env('PAYMENT_LANGGANAN_HARGA', 75000),
];
