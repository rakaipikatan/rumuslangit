<?php

return [
    // Folder salinan backup database (di luar web root; izin 700). Berkas berizin 600.
    'direktori' => env('BACKUP_DIR', '/home/ubuntu/backup-db'),

    // Rotasi: simpan semua backup N hari terakhir, lalu 1 backup per bulan untuk M bulan terakhir.
    'harian_simpan'  => (int) env('BACKUP_HARIAN_SIMPAN', 14),
    'bulanan_simpan' => (int) env('BACKUP_BULANAN_SIMPAN', 6),

    // Skema yang di-backup (skema uji "rumuslangit_test" sengaja tidak ikut).
    'skema' => env('BACKUP_SKEMA', 'public'),

    // Tabel inti yang WAJIB ada di dalam dump agar backup dianggap sah.
    'tabel_wajib' => ['users', 'orders', 'subscriptions', 'ai_reports', 'affiliates', 'affiliate_commissions', 'email_logs'],
];
