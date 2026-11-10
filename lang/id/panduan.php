<?php

return [
    'title' => 'Panduan Pengguna',
    'intro' => 'Panduan singkat memakai Rumus Langit: dari mendapat laporan sampai memahami harga dan jatah.',

    'langkah_title' => 'Cara mendapatkan laporan',
    'langkah' => [
        'Isi data lahir Anda (tanggal, jam, dan tempat lahir) — dari sini weton, shio, dan zodiak Anda dihitung.',
        'Verifikasi email dengan kode 6 digit yang kami kirim, supaya laporan dan pembelian Anda tersimpan di akun.',
        'Di katalog, pilih laporan yang Anda inginkan. Laporan berbayar membuka halaman pembayaran; Tafsir Mimpi gratis.',
        'Jawab kuesioner Ya/Tidak singkat (dan data pasangan untuk laporan Jodoh), lalu tunggu beberapa saat sampai laporan siap.',
        'Baca laporan Anda. Laporan tersimpan di akun dan bisa dibuka lagi kapan saja lewat tombol "Lihat laporan terakhir saya".',
    ],

    'paket_title' => 'Pilihan paket dan harga',
    'paket' => [
        ['judul' => 'Laporan satuan — Rp :satuan', 'isi' => 'Untuk mencoba satu laporan. Anda mendapat :n jatah untuk laporan itu (lihat "Apa itu jatah?" di bawah). Hasil yang sudah dibuat tersimpan selamanya.'],
        ['judul' => 'Langganan bulanan — Rp :bulanan', 'isi' => 'Semua laporan tanpa jatah (batas wajar :batas laporan per hari), ditambah Angka & Warna Keberuntungan Harian dan email Hoki Harian selama langganan aktif.'],
        ['judul' => 'Langganan tahunan — Rp :tahunan', 'isi' => 'Sama dengan bulanan untuk 12 bulan, dengan harga lebih hemat dibanding 12 kali bayar bulanan.'],
    ],
    'paket_saran' => 'Tips: bila Anda ingin membuka lebih dari satu laporan, langganan umumnya lebih murah daripada membeli satuan.',

    'jatah_title' => 'Apa itu jatah?',
    'jatah_intro' => 'Setiap pembelian laporan satuan memberi :n jatah. Satu jatah terpakai saat Anda membuat laporan dengan jawaban kuesioner yang BERBEDA dari sebelumnya.',
    'jatah_contoh_title' => 'Contoh',
    'jatah_contoh' => [
        'Anda mengisi kuesioner dengan jawaban A → laporan dibuat, jatah berkurang 1 (sisa :n−1).',
        'Anda mengirim jawaban A yang sama lagi di hari yang sama → yang tampil adalah laporan yang sama persis, jatah tidak berkurang.',
        'Besoknya Anda mengirim jawaban A yang sama → dibuatkan laporan baru (hasilnya bisa berbeda), jatah tetap tidak berkurang.',
        'Anda mengubah jawaban menjadi B → laporan baru dengan jawaban berbeda, jatah berkurang 1 lagi.',
        'Jatah habis → laporan lama tetap bisa dibuka; untuk membuat dengan jawaban baru, beli lagi (dapat :n jatah baru) atau berlangganan.',
    ],
    'jatah_catatan' => 'Mengganti bahasa (Indonesia ↔ Inggris) dengan jawaban yang sama membuat laporan dalam bahasa baru tanpa memotong jatah. Pembuatan yang gagal tidak memotong jatah.',

    'hari_title' => 'Mengapa hasil bisa berbeda di hari lain?',
    'hari_isi' => 'Dalam satu hari (WIB), jawaban yang sama selalu menampilkan hasil yang sama. Setelah pergantian hari (pukul 00.00 WIB), jawaban yang sama membuat laporan baru, sehingga wording dan rekomendasinya dapat berbeda. Laporan lama tidak hilang.',

    'bayar_title' => 'Pembayaran',
    'bayar' => [
        'Pembayaran memakai QRIS, bisa dari e-wallet atau mobile banking Indonesia (GoPay, OVO, DANA, ShopeePay, aplikasi bank).',
        'Setelah membayar, halaman akan berpindah sendiri ke kuesioner begitu pembayaran terkonfirmasi (biasanya beberapa detik).',
        'Bukti pembayaran dikirim otomatis ke email Anda. Tidak ada di Inbox? Cek folder Spam atau Junk.',
        'Membayar dari luar negeri? Simpan gambar QR lalu buka lewat fitur pindai/unggah QR di aplikasi bank atau e-wallet Indonesia Anda.',
    ],

    'akun_title' => 'Akun dan login',
    'akun' => [
        'Masuk dengan kode email: di halaman Login, masukkan email → kode 6 digit dikirim (berlaku 5 menit, cek Spam bila tidak ada).',
        'Ingin login dengan password? Buka menu Pengaturan (ikon ⚙) dan buat password. Kode email tetap bisa dipakai kapan saja.',
        'Laporan dan pembelian Anda mengikuti email akun, jadi bisa dibuka dari perangkat mana pun setelah login.',
    ],

    'faq_title' => 'Pertanyaan umum',
    'faq' => [
        ['q' => 'Saya sudah membayar tetapi akses belum terbuka.', 'a' => 'Tunggu sampai 1–2 menit lalu muat ulang halaman. Cek email bukti pembayaran. Jika tetap belum terbuka, hubungi kami dengan menyebut nomor order (RL-…) dari email bukti pembayaran.'],
        ['q' => 'Laporan saya hilang?', 'a' => 'Tidak. Buka laporannya di katalog lalu pilih "Lihat laporan terakhir saya". Laporan tersimpan selamanya di akun.'],
        ['q' => 'Kenapa hasil hari ini berbeda dari kemarin untuk jawaban yang sama?', 'a' => 'Karena setiap hari laporan dibuat baru. Dalam satu hari, hasilnya selalu sama.'],
        ['q' => 'Berapa laporan yang bisa dibuat pelanggan langganan?', 'a' => 'Tanpa jatah, dengan batas wajar :batas laporan per hari agar layanan tetap lancar untuk semua. Tafsir Mimpi (gratis) dibatasi :gratis kali per hari.'],
    ],

    'disclaimer' => 'Rumus Langit adalah platform konsultasi metafisika dan hiburan reflektif — bukan pengganti nasihat medis, hukum, keuangan, atau profesional lainnya.',
];
