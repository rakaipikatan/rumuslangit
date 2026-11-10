# Dokumentasi Teknis — Rumus Langit

> Platform konsultan metafisika & wellness berbasis AI (weton, shio, zodiak + laporan naratif dari Gemini).
> Dokumen ini mendeskripsikan kondisi kode **per 10 November 2026**. Untuk visi produk & roadmap, lihat [PRD-RumusLangit.md](PRD-RumusLangit.md) (catatan: PRD menyebut Laravel 11, kenyataannya sudah Laravel 13).

---

## 1. Ringkasan

| Aspek | Nilai |
|---|---|
| Domain | rumuslangit.biz.id |
| Framework | Laravel 13 (`laravel/framework` v13.40), PHP 8.3 |
| Database | PostgreSQL (remote), sqlite hanya cadangan lokal |
| Cache / Session / Queue | Redis (`predis`) |
| Queue dashboard | Laravel Horizon (`/horizon`, terkunci — gate kosong, hasilnya 403) |
| AI | Google Gemini (`gemini-2.5-flash-lite` default) via REST |
| PDF | `barryvdh/laravel-dompdf` (terpasang; model `PdfDownload` ada, belum ada alur unduh PDF di route) |
| Frontend | Blade + Tailwind CSS 4 + Vite 8 (tanpa SPA) |
| Web server | nginx + php-fpm 8.3 (pool `www`, user `www-data`) |
| Bahasa UI | Indonesia (`id`) dan Inggris (`en`) |
| Pembayaran | **QRIS** via SumoPod Managed Payment (otomatis lewat webhook). Transfer bank manual untuk pelanggan sudah dihapus (10 Nov 2026) |

Ada juga situs lain di server yang sama (`assessly.biz.id`), vhost terpisah.

---

## 2. Alur Pengguna

```
Landing (/) ─► Isi data lahir (/mulai-analisis) ─► POST /proses
      │                                                  │
      │                         KalkulasiEngine hitung weton/shio/zodiak/elemen/radar
      │                         User + BirthProfile dibuat, id disimpan di session
      ▼                                                  ▼
 Hasil trial (/hasil) ◄──── job profil AI (feature_id=0) dijalankan di queue
      │
      ├─► Verifikasi email via OTP (/otp/kirim → /otp/verifikasi)
      ▼
 Katalog fitur (/analisis)  ─► pilih fitur ─► /laporan/{id}
      │                                         │
      │            punya akses? ── ya ──► form kuesioner ─► POST ─► queue ─► status (polling) ─► hasil
      │                         └─ tidak ─► paywall ─► /payment/{id} ─► transfer ─► admin konfirmasi
```

Poin penting:

- **Tidak ada registrasi klasik.** Identitas pengguna = baris `users` yang dibuat dari form data lahir; `user_id` disimpan di **session**. Login email+password (`/login`) hanya untuk akun yang sudah punya password (mis. dibuat admin).
- **Akses fitur** (`User::hasAccessToFeature`): pelanggan aktif **atau** punya order `settlement` untuk fitur itu.
- **Trial** dibatasi per email: satu email (di-hash SHA-256) hanya bisa pakai trial sekali (`email_verifications.trial_used_at`). Pembatasan dimatikan saat `APP_ENV=local`.
- **Hasil AI di-cache**: laporan identik (hash dari fitur + kalkulasi + jawaban + tarot + locale) dipakai ulang tanpa memanggil Gemini lagi; selain itu respons Gemini di-cache 24 jam di Redis.

---

## 3. Fitur Produk

Didefinisikan di [app/Data/FiturData.php](app/Data/FiturData.php) (metadata bahasa-netral + teks per locale). Harga per laporan **Rp29.000**.

| ID | Hub | Fitur | Catatan |
|---|---|---|---|
| 0 | — | Profil karakter (trial) | Otomatis, gratis, tidak ada di katalog |
| 1 | Wealth | Arah Mata Angin Rezeki | |
| 2 | Wealth | Potensi Bisnis & Gaya Investasi | |
| 3 | Wealth | Hari Baik & Momentum Usaha | |
| 4 | Wealth | Arah Tempat Usaha & Ruko | |
| 5 | Social | Kalkulator Jodoh & Asmara | Wajib isi data pasangan |
| 6 | Social | Radar Toksisitas & Politik Kantor | |
| 7 | Social | Karakter Anak & Pola Asuh | |
| 8 | Wellness | Audit & Analisis Nama | |
| 9 | Wellness | Kesehatan & Vitalitas | Tambahan input golongan darah |
| 10 | Wellness | Panduan Ruwat & Mitigasi Sial | |
| 11 | Wellness | Angka & Warna Keberuntungan Harian | `subscriber_only`, harga 0 |
| 12 | Traffic Driver | Tafsir Mimpi | `freemium`, input teks bebas (maks 500 karakter), form khusus `form_mimpi` |

Fitur 1–10 memakai 10 pertanyaan Ya/Tidak (`q1..q10`). Tarot (opsional, maks 3 kartu) dari [app/Data/TarotData.php](app/Data/TarotData.php) lewat `/tarot/pilih`.

### Harga & langganan (config: [config/payment.php](config/payment.php))

| Produk | Harga | Marker `orders.feature_id` |
|---|---|---|
| Laporan satuan | Rp29.000 | 1–12 |
| Langganan bulanan | Rp49.000 | `-1` (`Order::SUBSCRIPTION_MONTHLY_FEATURE_ID`) |
| Langganan tahunan | Rp350.000 | `-2` (`Order::SUBSCRIPTION_YEARLY_FEATURE_ID`) |

Fitur **freemium (ID 12, Tafsir Mimpi) terbuka untuk semua pengguna** tanpa bayar; fitur 11 hanya untuk pelanggan; halaman `/payment/{id}` untuk fitur bernominal 0 dialihkan (tidak pernah membuat order Rp0) dan kartu beli eceran disembunyikan di paywall fitur khusus langganan. Pelanggan aktif mengakses semua fitur dan menerima email **Hoki Harian** pukul 06.00 WIB.

---

## 4. Arsitektur Kode

```
app/
├── Console/Commands/      hoki:kirim, subscription:sync
├── Data/                  FiturData (katalog fitur), TarotData
├── Exceptions/            GeminiRateLimitException
├── Http/
│   ├── Controllers/       Trial, Report, Payment, Otp, Auth, Admin, Wilayah, Locale, Tarot
│   └── Middleware/        SetLocale, CaptureReferral, AdminMiddleware (alias `admin`)
├── Jobs/GenerateAIReport  panggilan Gemini di queue
├── Mail/                  OtpMail, HokiHarianMail
├── Models/                User, Order, Subscription, AiReport, BirthProfile, QuestionnaireSession,
│                          EmailVerification, PdfDownload, Affiliate, AffiliateCommission, Wilayah*
├── Providers/             AppServiceProvider, HorizonServiceProvider
└── Services/
    ├── KalkulasiEngine    hitung weton/shio/zodiak/elemen/radar (murni PHP, deterministik)
    ├── GeminiService      bangun prompt per fitur+locale, panggil Gemini, perbaiki JSON
    └── EmailOtpService    OTP 6 digit, TTL 5 menit di cache
```

### 4.1 KalkulasiEngine
Dihitung **tanpa AI**: weton (hari + pasaran Jawa, neptu), shio (tahun baru Imlek disederhanakan: sebelum Februari dihitung tahun sebelumnya), zodiak Barat, elemen (dari digit akhir tahun), dan 5 skor "radar" (karir, asmara, cuan, kesehatan, spiritual) dari seed tanggal+jam lahir. Skor radar bersifat pseudo-acak deterministik, bukan perhitungan tradisional.

### 4.2 GeminiService & GenerateAIReport
- `GeminiService::generate()` membangun prompt (ID/EN) per fitur, `temperature` 0.87 (0.75 untuk Tafsir Mimpi), `maxOutputTokens` dari config, timeout 90 detik.
- HTTP 429 → `GeminiRateLimitException` (dengan `retryAfterSeconds`); 503 → retry/backoff.
- `GenerateAIReport` (queue `default`): `tries=10`, `maxExceptions=3`, backoff 30/60/120/180 detik, **rate limiter global 12 panggilan/menit** (`gemini-api-calls`) plus jitter saat di-release.
- Fitur 0 (profil) menyimpan **JSON** di `ai_reports.response_text`; fitur lain menyimpan teks. `repairJson()` memperbaiki newline literal dan **trailing comma**; hasil profil **divalidasi** sebelum disimpan/di-cache — jika tetap rusak, job melempar exception dan di-retry. Pada sisi baca (`TrialController::decodeProfil`) JSON rusak diperbaiki dulu; jika tak terpulihkan, baris dibuang dan profil digenerate ulang. Header autentikasi Gemini: `x-goog-api-key`. Tafsir Mimpi dicoba sinkron dulu, jatuh ke queue jika gagal.

**Status uji (10 Nov 2026):** panggilan langsung ke `gemini-2.5-flash-lite` → HTTP 200 dan respons benar; autentikasi lewat header `x-goog-api-key` juga berhasil (bisa menggantikan `?key=` di query string). Tidak ada failed job; 13 laporan tersimpan, semuanya terisi (0 macet). Error Gemini terakhir di log tercatat Juni 2026.

### 4.3 Pembayaran
Satu-satunya metode pembayaran pelanggan adalah **QRIS** (bagian 4.3b). Transfer bank manual (kode unik 3 digit + konfirmasi admin) **dihapus pada 10 Nov 2026**: tidak ada lagi pilihan rekening di halaman bayar, config `payment.bank_accounts` dan env `PAYMENT_BCA_*`/`PAYMENT_CIMB_*` dibuang. Kolom lama `orders.unique_code`, `transfer_amount`, `bank_tujuan` tetap ada di database (riwayat; tidak dihapus). Kedaluwarsa order ditangani SumoPod (`payment.expired` → status `expire`; masa berlaku link `PAYMENT_EXPIRE_HOURS`, maks. 24 jam).

Konfirmasi admin (`POST /admin/orders/{id}/konfirmasi`) **dipertahankan sebagai cadangan** bila webhook gagal, misalnya order QRIS pending yang pembayarannya terbukti masuk di dashboard SumoPod. Ia memakai `OrderSettlementService` yang sama dengan webhook.

### 4.3b Pembayaran QRIS (SumoPod Managed Payment)
Metode pembayaran tunggal. Bila `SUMOPOD_PAY_API_KEY` kosong, halaman `/payment/{id}` menampilkan pesan "QRIS sedang tidak tersedia" (tidak ada jalur alternatif).

1. `POST /payment/{id}` → order `pending` (`payment_method='qris'`, `gateway_order_id='RL-xxxx'`, tanpa kode unik) → [SumopodPayService](app/Services/SumopodPayService.php) `POST {base}/api/v1/payments` (header `X-Api-Key`, `payment_method_type_code=qris`, kedaluwarsa maks. 24 jam) → `payment_id`, `payment_link_url`, `payment_expires_at` disimpan → user di-redirect ke halaman bayar SumoPod. Submit ulang memakai order yang sama (tanpa payment ganda). API gagal → order `cancel` + pesan error.
2. SumoPod memanggil **`POST /webhook/sumopod`** ([SumopodWebhookController](app/Http/Controllers/SumopodWebhookController.php)). Dikecualikan dari CSRF, diamankan **signature Svix** (HMAC-SHA256 atas `{svix-id}.{svix-timestamp}.{raw body}` dengan `SUMOPOD_PAY_WEBHOOK_SECRET`, toleransi waktu 5 menit, perbandingan constant-time). Signature salah → 401.
2b. **Alur setelah bayar:** `buatPembayaran` mengirim `success_return_url` = `/payment/selesai/{gateway_order_id}` dan `cancel_return_url` = halaman bayar produk itu (jika API menolak override, otomatis diulang tanpa override sehingga alamat bawaan dashboard dipakai). Halaman `payment.selesai` (hanya pemilik order): lunas → langsung ke kuesioner fitur (atau katalog untuk langganan) dengan pesan sukses; belum masuk → halaman tunggu yang polling tiap 2,5 detik dan berpindah otomatis begitu webhook melunasi order (setelah 90 detik muncul petunjuk bahwa akses terbuka otomatis); batal/kedaluwarsa → kembali ke halaman bayar. Katalog dan daftar "Analisis lainnya" menandai fitur yang sudah dibeli dengan "Sudah dibeli" (bukan harga).
3a. **Pengingat folder Spam (aturan untuk SEMUA pengiriman email):** domain pengirim masih baru sehingga email awal sering masuk Spam. Karena itu setiap email dan setiap pesan di layar yang menyebut "email telah dikirim" **wajib** memuat pengingat cek folder Spam/Junk (dan di dalam email: tandai "Bukan spam"). Sudah diterapkan di: modal OTP (`hasil.otp.kode_info`), login kode (`auth.kode_terkirim`, `auth.kode_desc`), pesan sukses bayar (`payment.selesai_sukses_*`), email OTP, bukti bayar, Hoki Harian, dan persetujuan afiliasi; halaman `/afiliasi` sudah memuatnya. **Email baru yang ditambahkan di masa depan harus mengikuti aturan ini** (ada tes yang memeriksanya).
3b. **Bukti bayar otomatis:** begitu order menjadi lunas (webhook QRIS **atau** konfirmasi admin), `OrderSettlementService` mengantrekan email [BuktiBayarMail](app/Mail/BuktiBayarMail.php) ke pelanggan (HTML + teks polos): nomor order (`RL-...`), produk, masa aktif (langganan), metode, waktu bayar (WIB), akun, total, tombol ke kuesioner/katalog, dan catatan "bukan faktur pajak". Dikirim **lewat queue** (webhook harus membalas < 10 detik), hanya sekali per order (pelunasan idempoten), dan kegagalan email tidak membatalkan pelunasan (hanya dicatat di log). Satu pembayaran = satu kredit email SumoPod. Pembayaran gagal/nominal salah tidak mengirim apa pun.
3. `payment.completed` → nominal harus sama dengan order, lalu [OrderSettlementService](app/Services/OrderSettlementService.php) melunasi order (idempoten, dengan row lock): membuka akses fitur / mengaktifkan langganan dan mencatat komisi afiliasi. `payment.failed` → `cancel`; `payment.expired` → `expire`; `payment.test` dan order tak dikenal → 200 (dicatat di log).

**Sandbox vs Live:** key Sandbox hanya berlaku di host sandbox. Untuk uji, set `SUMOPOD_PAY_BASE_URL=https://api-pay-sandbox.sumopod.com` (hasil uji 10 Nov 2026: key Sandbox di host Live → `401 Unauthorized`; di host Sandbox → payment berhasil dibuat). Untuk produksi: `SUMOPOD_PAY_BASE_URL=https://api-pay.sumopod.com` + key Live, lalu `php artisan config:cache`.

**Batas minimum SumoPod: Rp1.000** (nominal lebih kecil ditolak `400 INVALID_INPUT`), jadi harga uji minimal 1000.

**Harga uji (sementara):** `PAYMENT_TEST_PRICE` + `PAYMENT_TEST_EMAIL` di `.env` menurunkan harga **hanya untuk satu email terverifikasi** (untuk tes transaksi Live bernominal kecil). Pengguna lain tetap membayar harga normal. Setelah pengujian, **kosongkan kedua variabel** lalu `php artisan config:cache`.

Biaya QRIS menurut dashboard SumoPod: 0,7% + Rp300 (dipotong dari saldo merchant), dana cair T+2. Kolom baru `orders.payment_id` (unik), `payment_link_url`, `payment_expires_at` (migration `2026_11_10_150000_...`).

### 4.4 Program afiliasi
- Afiliator mendaftar lewat halaman publik `/afiliasi` dan **disetujui admin** (atau ditambahkan manual oleh admin); kode referral unik, komisi default `AFFILIATE_DEFAULT_COMMISSION` (20%).
- Link `?ref=KODE` ditangkap `CaptureReferral` (first-touch, di session). Saat `User` pertama dibuat, `referred_by_affiliate_id` diisi **permanen**.
- Tiap order `settlement` mencatat komisi `unpaid` di `affiliate_commissions` (satu per order, idempoten) lewat `OrderSettlementService::catatKomisiAfiliasi`. Afiliator nonaktif tidak dapat komisi baru.
- **Aturan komisi (juga menjadi Syarat & Ketentuan yang dibaca dan disetujui afiliator di `/afiliasi`, dan dikirim di email persetujuan; teks di `lang/*/afiliasi.php`, angka dari config):**
  1. Komisi = persentase × harga yang dibayar (sebelum biaya gateway dan pajak), untuk setiap pembayaran berhasil pelanggan yang direferensikan.
  2. **Batas waktu:** hanya untuk pembayaran dalam `AFFILIATE_COMMISSION_MONTHS` (12) bulan sejak **pembayaran pertama** pelanggan itu (termasuk perpanjangan langganan); sesudahnya tanpa komisi.
  3. **Tanpa komisi untuk pembelian sendiri:** email pelanggan sama dengan email afiliator (tidak peka huruf besar-kecil).
  4. **Masa tahan** `AFFILIATE_HOLD_DAYS` (14) hari: admin hanya bisa menandai lunas komisi yang sudah lewat masa tahan ("Siap dibayar"); yang masih "Ditahan" tidak ikut dan masuk periode berikutnya. Dibayar manual tiap tanggal 25.
  5. Tidak ada komisi untuk transaksi gagal/batal/refund/terindikasi penipuan (komisi hanya dicatat saat order `settlement`; pembatalan setelahnya perlu dibatalkan manual oleh admin — belum ada otomatisasi refund).
- Admin menandai lunas komisi yang siap dibayar setelah transfer manual selesai.

#### Pendaftaran afiliator (publik)
- Halaman **`/afiliasi`** (tautan di footer semua halaman): penjelasan program (komisi `AFFILIATE_DEFAULT_COMMISSION`, default 20%, bayar tiap tanggal 25) + formulir (nama, email, HP, rencana promosi opsional, rekening payout, persetujuan).
- Disimpan di tabel **`affiliate_applications`** dengan status `pending` — **tidak** langsung menjadi afiliator. Perlindungan spam: 5 kiriman/jam per IP, kolom honeypot `website`, validasi format HP/rekening, satu email hanya satu pendaftaran menunggu / satu afiliator.
- Admin → **Afiliator**: kartu "Pendaftar menunggu persetujuan" (badge jumlah di menu). **Setujui** (komisi % bisa diubah per orang) membuat `Affiliate` aktif dengan kode referral otomatis (`NAMADEPAN` + 2 digit, unik) dan mengirim email `AffiliateDisetujuiMail` berisi link `?ref=KODE`; bila email gagal, link ditampilkan di pesan admin untuk dikirim manual. **Tolak** menandai `rejected`. Tambah afiliator manual (modal) tetap ada.

### 4.5 Wilayah
Dropdown berjenjang provinsi → kota → kecamatan → kelurahan lewat `/api/wilayah/*`. Provinsi dan kota di-seed; kecamatan/kelurahan **di-seed lazy** saat pertama kali diminta (`WilayahController::seedKecamatan/seedKelurahan`).

### 4.6b Login pengguna
Pengguna **tidak punya password** (akun dibuat dari form data lahir + verifikasi email OTP), sehingga jalur login utama adalah **kode email**: `/login` → masukkan email → kode 6 digit dikirim (berlaku 5 menit) → masuk ke katalog. Rinciannya:
- Hanya akun dengan email **terverifikasi** yang dikirimi kode; respons selalu sama (tidak membocorkan apakah email terdaftar).
- Batas: 6 permintaan/menit per IP, 3 kode/10 menit per email, maks. **5 tebakan salah** per kode (setelah itu kode dibatalkan), 10 verifikasi/menit per IP.
- Login password (`POST /login`, 10/menit) tetap ada, dilipat di bawah ("Punya password?"), untuk akun yang dibuat admin (user testing).
- **Menu Pengaturan** (`/pengaturan`, ikon ⚙ di navbar): pengguna login dengan email terverifikasi dapat membuat password (akun belum punya) atau mengubahnya (wajib password lama), min. 8 karakter + konfirmasi, 5 percobaan/menit. Kode email tetap bisa dipakai kapan saja.
- Pengguna yang kehilangan sesi/cookie cukup login ulang dengan kode email; data laporan dan pembelian mengikuti akun (berdasarkan email).

### 4.6 Lokalisasi
`SetLocale` membaca `session('locale')` (default `id`); ganti bahasa lewat `/lokal/{id|en}`. File terjemahan di [lang/](lang/). Laporan AI disimpan per `locale`.

---

## 5. Rute

| Area | Rute |
|---|---|
| Publik | `/`, `/mulai-analisis`, `POST /proses`, `/hasil`, `/profil-status` (AJAX), `/analisis` (katalog), `/lokal/{locale}`, `/tarot/pilih`, `/health` |
| Auth | `/login`, `POST /login` (password), `POST /login/kode` + `POST /login/kode/verifikasi` (kode email), `POST /logout`, `GET /pengaturan`, `POST /pengaturan/password` |
| Afiliasi | `GET/POST /afiliasi` (publik), admin: `POST /admin/affiliates/pendaftar/{id}/setuju`, `.../tolak` |
| OTP | `POST /otp/kirim`, `POST /otp/verifikasi` |
| Laporan | `/laporan/{id}` (form), `POST`, `/laporan/{id}/status` (polling), `/laporan/{id}/hasil` — throttle 10 req/menit |
| Payment | `GET/POST /payment/{id}` `POST /webhook/sumopod` (id numerik atau `langganan`, `langganan-bulanan`, `langganan-tahunan`) |
| Wilayah | `/api/wilayah/{provinsi,kota,kecamatan,kelurahan}` |
| Admin | `/admin/login`, `/admin` (dashboard), `/users`, `/reports`, `/orders`, `/affiliates`, `/system` |
| Sistem | `/up` (health bawaan Laravel), `/health` (JSON `{status, ts}`), `/horizon` |

Lihat daftar lengkap dengan `php artisan route:list` (69 rute).

---

## 6. Database

Tabel utama (PostgreSQL): `users`, `birth_profiles`, `questionnaire_sessions`, `ai_reports`, `orders`, `subscriptions`, `pdf_downloads`, `email_verifications`, `affiliates`, `affiliate_commissions`, `wilayah_provinsi|kota|kecamatan|kelurahan`, serta tabel framework (`sessions`, `cache`, `jobs`, `failed_jobs`, `job_batches`, `password_reset_tokens`).

Relasi inti:

```
users 1─1 birth_profiles
users 1─* orders          users 1─* subscriptions      users 1─* ai_reports
users *─1 affiliates      affiliates 1─* affiliate_commissions *─1 orders
```

Kolom khusus: `orders.gateway_order_id (RL-xxxx), payment_id, payment_link_url, payment_expires_at` (kolom lama `unique_code / transfer_amount / bank_tujuan` tak dipakai lagi), `ai_reports.data_hash / locale`, `users.referred_by_affiliate_id`.

> **Perhatian:** migration project ini mengandung SQL mentah khusus Postgres (mis. `ALTER COLUMN ... DROP NOT NULL`), sehingga tidak bisa dijalankan di sqlite.

---

## 7. Admin Panel (`/admin`)

Login memakai `ADMIN_USERNAME` / `ADMIN_PASSWORD` dari `.env` (flag `admin_logged_in` di session). Menu: **Dashboard** (statistik), **Users** (cari, buat user testing, reset password, hapus), **Reports** (laporan AI), **Orders** (konfirmasi/tolak), **Afiliasi** (buat, aktif/nonaktif, detail, tandai lunas), **System** (info, clear cache, bersihkan failed jobs).

---

## 8. Konfigurasi Environment

Variabel yang dipakai (nilai rahasia jangan di-commit; `.env` sudah di-gitignore):

| Grup | Variabel |
|---|---|
| App | `APP_NAME, APP_ENV, APP_KEY, APP_DEBUG, APP_URL, APP_LOCALE` |
| Database | `DB_CONNECTION=pgsql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_SSLMODE` |
| Redis | `SESSION_DRIVER=redis, QUEUE_CONNECTION=redis, CACHE_DRIVER=redis, REDIS_*` |
| Mail | `MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_SCHEME (smtps untuk port 465), MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_*`. Domain pengirim harus punya SPF/DKIM (DNS domain dikelola di SumoPod); tanpa itu email OTP masuk spam/terbuang |
| Gemini | `GEMINI_API_KEY, GEMINI_MODEL, GEMINI_MAX_OUTPUT_TOKENS` |
| Pembayaran | `PAYMENT_EXPIRE_HOURS, PAYMENT_LANGGANAN_BULANAN_HARGA, PAYMENT_LANGGANAN_TAHUNAN_HARGA` |
| Admin | `ADMIN_USERNAME, ADMIN_PASSWORD` |
| SumoPod Payment (QRIS) | `SUMOPOD_PAY_API_KEY, SUMOPOD_PAY_WEBHOOK_SECRET, SUMOPOD_PAY_BASE_URL` (default `https://api-pay.sumopod.com`) |
| Legacy (tidak dipakai kode saat ini) | `FONNTE_*, WHATSAPP_PROVIDER, WAHA_*, DUITKU_*` |

`.env.example` masih berisi template bawaan Laravel (sqlite) dan **belum** mencantumkan variabel di atas.

---

## 9. Menjalankan & Deploy

### Pengembangan lokal
```bash
composer install && npm install --ignore-scripts
cp .env.example .env && php artisan key:generate   # lalu isi DB/Redis/Gemini
php artisan migrate
composer dev        # serve + queue:listen + pail + vite
```

### Produksi (server `genji`)
```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.3-fpm
php artisan horizon:terminate      # supervisor akan menjalankan ulang Horizon
```

Proses yang harus hidup: **nginx**, **php-fpm**, **`php artisan horizon`** (worker queue), dan **cron** untuk scheduler (`* * * * * php artisan schedule:run`).

Scheduler ([bootstrap/app.php](bootstrap/app.php)): `hoki:kirim` harian 06:00 WIB; `subscription:sync` tiap jam.

Perintah artisan kustom: `hoki:kirim`, `subscription:sync`, `mail:uji {email}` (kirim email uji + tampilkan konfigurasi SMTP aktif; berguna setelah mengganti `MAIL_*`).

> **Jebakan config cache:** `bootstrap/cache/config.php` menyimpan konfigurasi produksi. Setelah mengubah `.env`, jalankan `php artisan config:cache`. Pastikan folder `storage/framework/views` dan `bootstrap/cache` dapat ditulis grup `www-data` (`chgrp -R www-data` + `chmod -R g+w`), kalau tidak halaman bisa 500 saat Blade dikompilasi ulang.

---

## 10. Testing

```bash
php artisan test       # atau: composer test
```

50 tes: `KalkulasiEngineTest`, `RepairJsonTest` (unit), `AdminLoginTest`, `SubscriptionPaymentTest`, `AffiliateTest`, `ProfilStatusTest`, `SumopodPaymentTest` (checkout QRIS, webhook: signature, replay, idempotensi, nominal, afiliasi), serta tes contoh.

- **Database tes terisolasi.** Tes yang butuh DB memakai trait [tests/Concerns/UsesPostgresTestSchema.php](tests/Concerns/UsesPostgresTestSchema.php): skema PostgreSQL terpisah **`rumuslangit_test`** di server DB yang sama (dibuat dan dimigrasi otomatis), tiap tes dibungkus transaksi yang di-rollback. Skema produksi `public` tidak disentuh, dan ada pengaman yang menggagalkan tes bila skema aktif bukan `rumuslangit_test`. (Migration memakai SQL khusus Postgres sehingga sqlite tidak bisa dipakai.)
- **Cache produksi diabaikan.** [tests/TestCase.php](tests/TestCase.php) mengarahkan `APP_CONFIG_CACHE` dan `APP_ROUTES_CACHE` ke file yang tidak ada, jadi `bootstrap/cache/*.php` (config/route produksi) tidak memengaruhi tes dan `php artisan test` aman dijalankan langsung.

---

## 11. Keamanan

### Status patch (10 Nov 2026)
- **PHP2Shell (CVE-2026-109618 / CVE-2026-109619, RCE tanpa autentikasi):** paket Ubuntu `php8.3 8.3.6-0ubuntu0.24.04.13` memuat patch `CVE-2026-109618_109619.patch` (Ubuntu mem-backport; nomor versi tetap 8.3.6). php-fpm berjalan sejak 8 Nov, setelah paket ter-patch. Hardening tambahan: `session.upload_progress.enabled=0` di `/etc/php/8.3/fpm/conf.d/99-hardening.ini` (fitur ini tidak dipakai aplikasi). Pencarian web shell / file PHP mencurigakan: tidak ditemukan.
- **CVE-2026-115560** (RCE lanjutan yang dilaporkan belum punya patch pada saat pencarian): belum ada patch/mitigasi terverifikasi — pantau advisory PHP dan `apt` (`unattended-upgrades`).
- **Dependensi Composer:** `laravel/framework` 13.12 → 13.40 (termasuk advisory *critical* TokenGuard), Symfony, Guzzle, dompdf, league/commonmark, flysystem diperbarui. `composer audit` → bersih.

### Temuan yang sudah diperbaiki (10 Nov 2026)
1. **Login admin:** perbandingan kredensial kini constant-time (`hash_equals`), mendukung `ADMIN_PASSWORD` plaintext atau hash bcrypt (`$2y$...`), menolak kredensial kosong, regenerasi session saat login, dan `POST /admin/login` dibatasi **5 percobaan/menit** (`throttle:5,1`).
2. **Rekening bank** dikeluarkan dari kode dan `.env` (fitur transfer manual dihapus). **Catatan:** nomor lama tetap ada di riwayat git (commit sebelumnya) — menghapusnya butuh menulis ulang riwayat, belum dilakukan.
3. **API key Gemini** dikirim lewat header `x-goog-api-key`, bukan query string.
4. **Tes tidak lagi menyentuh data produksi** (skema `rumuslangit_test`, lihat bagian 10).
5. Kode legacy WhatsApp (`otp:test`, `waha:status`, config `whatsapp/fonnte/waha/duitku`) dihapus.

### Yang perlu diketahui
- Session pengguna hanya `user_id` — pemegang session mewarisi akses; OTP email adalah satu-satunya verifikasi kepemilikan.
- Variabel `.env` legacy (`FONNTE_*`, `WAHA_*`, `WHATSAPP_PROVIDER`, `DUITKU_*`) sudah tidak dibaca kode dan boleh dihapus dari `.env`.

---

## 12. Kondisi Kode yang Perlu Diketahui

- Model `PdfDownload` + paket dompdf sudah ada tanpa route unduh PDF.
- `payment.expire_hours` belum dipakai oleh job apa pun (order pending tidak kedaluwarsa otomatis).
- PRD menyebut harga lama (Rp19.000 / Rp15.000 per bulan) dan Laravel 11; harga sebenarnya di bagian 3.
- Setelah mengubah kode di server produksi: `config:cache`, `route:cache`, `view:cache`, `sudo systemctl reload php8.3-fpm`, dan **`php artisan horizon:terminate`** (worker queue menyimpan kode lama di memori sampai di-restart; supervisor menjalankannya ulang otomatis).

---

## 13. Riwayat Perubahan Terbaru

> Aturan kerja: setiap perubahan **wajib** dicatat di dokumen ini dan di-push ke GitHub (`rakaipikatan/rumuslangit`, branch `main`).

| Commit | Isi |
|---|---|
| `900b7d9` | **Pengingat cek folder Spam/Junk** di semua email dan pesan layar terkait pengiriman email (OTP, login kode, bukti bayar, Hoki Harian, persetujuan afiliasi) + 3 tes (total 98) |
| `118db64` | **Email bukti pembayaran otomatis** setelah order lunas (queue, sekali per order, HTML+teks) + 5 tes (total 95) |
| `b50ffd9` | **Aturan komisi afiliasi:** batas 12 bulan sejak pembayaran pertama pelanggan, tanpa komisi untuk pembelian sendiri, masa tahan 14 hari sebelum bisa dibayar; **Syarat & Ketentuan** ditampilkan di `/afiliasi` dan dikirim di email persetujuan; +4 tes (total 90) |
| `a4673ee` | **Halaman pendaftaran afiliator** `/afiliasi` + persetujuan di admin + email link referral otomatis; migration `affiliate_applications`; +11 tes (total 86) |
| `7b7bfd4` | **Menu Pengaturan akun** untuk membuat/mengubah password (⚙ di navbar); +4 tes (total 75) |
| `24aa214` | **Login dengan kode email** (tanpa password) di `/login`, dengan pembatasan percobaan; login password dilipat. Admin → Users menampilkan "N laporan dibeli" (status `free` = bukan pelanggan langganan; pembelian satuan tidak mengubah status). +6 tes (total 71) |
| `ca24ee5` | **Transfer bank manual dihapus** (halaman bayar, controller, config rekening, teks bahasa, env `PAYMENT_BCA/CIMB`); QRIS jadi satu-satunya metode. Konfirmasi admin dipertahankan sebagai cadangan; rekening untuk **payout komisi afiliator** tidak terkait dan tetap. Tes disesuaikan (total 65) |
| `2b3b60b` | **Perbaikan alur pasca-bayar QRIS:** return URL per-payment ke halaman `payment.selesai` (tunggu konfirmasi → otomatis ke kuesioner), katalog & upsell menandai fitur yang sudah dibeli, pesan sukses; +8 tes (total 63) |
| `23f8049` | **Bugfix:** Tafsir Mimpi (freemium, Rp0) salah menampilkan paywall "Bayar Sekarang" dan membuat order QRIS; kini gratis untuk semua. Fitur Rp0 tak bisa dibeli eceran, harga uji tak berlaku untuk fitur gratis; +3 tes (total 55) |
| `6d66fd1` | Email OTP kini punya versi teks polos (mengurangi risiko spam) + tes; harga uji dinaikkan ke Rp1.000 (minimum SumoPod). DNS email: SPF, DKIM (`trx_ke._domainkey`), verifikasi SumoPod terpasang dan terverifikasi; MAIL_* dipindah ke SMTP SumoPod (`smtp.sumopod.com:465`, smtps). Disarankan menambah DMARC (`_dmarc` TXT `v=DMARC1; p=none;`) |
| `e178033` | Perintah `mail:uji` untuk memeriksa pengiriman email. Temuan: domain belum punya SPF/DKIM/DMARC sehingga OTP via Brevo tidak sampai; rencana pindah ke SMTP SumoPod (`smtp.sumopod.com:465`, `MAIL_SCHEME=smtps`) |
| (10 Nov 2026) | Kredensial admin diganti (di `.env`, bukan di repo): username baru, password acak 20 karakter disimpan sebagai hash bcrypt (`ADMIN_PASSWORD='$2y$...'`, tanda kutip tunggal wajib). Cadangan `.env` lama di `/home/ubuntu/backup/` |
| `128f84d` | **Pembersihan data:** semua user + data turunan (laporan AI, kuesioner, profil lahir, langganan, order) dihapus atas permintaan pemilik, dengan cadangan JSON di server (`/home/ubuntu/backup/`, di luar repo). Perbaikan: katalog dengan sesi usang (user sudah dihapus) kini diarahkan ke awal, bukan error; +1 tes (total 51) |
| `6ee9559` | Harga uji sementara per-email (`PAYMENT_TEST_PRICE`/`PAYMENT_TEST_EMAIL`) untuk tes transaksi Live bernominal kecil; +2 tes (total 50) |
| `e203f75` | Integrasi **QRIS SumoPod Managed Payment**: service klien API, webhook ber-signature Svix, `OrderSettlementService` bersama, pilihan metode di halaman pembayaran, migration kolom payment, 14 tes baru. **Belum aktif di produksi** sampai migration dijalankan dan `config:cache`/`route:cache` di-refresh (lihat bagian 9) |
| `080df47` | **Bugfix produksi:** profil karakter menggantung di "Sedang menganalisis..." — Gemini mengembalikan JSON dengan trailing comma sehingga data tersimpan tapi tak terbaca. Perbaikan: `repairJson` + validasi + self-healing + tes regresi. Plus: hardening login admin (throttle), rekening ke `.env`, tes pada skema DB terpisah, hapus kode WhatsApp legacy, API key Gemini via header |
| `0a837b8`, `7e8789e` | Patch keamanan: update dependensi Composer (laravel/framework 13.40, Symfony, Guzzle, dompdf, commonmark), hardening PHP2Shell, dokumentasi ini |
| `619f858` | Program afiliasi (referral, komisi, payout manual) |
| `5329d05` | Harga baru: laporan Rp29rb, langganan Rp49rb/bulan, tahunan Rp350rb |
| `077e5f8` | Fix crash langganan bulanan di paywall & aktivasi |
| `8554ac3` | Fix title-case singkatan DKI |
| `6283fcb` | Initial commit |
