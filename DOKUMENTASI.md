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
| Pembayaran | Transfer bank manual + kode unik (konfirmasi admin) **dan** QRIS via SumoPod Managed Payment (otomatis lewat webhook) |

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

Pelanggan aktif mengakses semua fitur dan menerima email **Hoki Harian** pukul 06.00 WIB.

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

### 4.3 Pembayaran manual
1. `POST /payment/{featureId}` membuat order `pending` (idempoten: order pending yang sama dipakai ulang), `unique_code` 1–999 yang dijamin unik di antara order pending bernominal sama, `transfer_amount = amount + unique_code`.
2. Pengguna transfer ke rekening di `config/payment.php` (BCA / CIMB Niaga).
3. Admin mencocokkan mutasi → `POST /admin/orders/{id}/konfirmasi` → status `settlement`; untuk langganan dibuat baris `subscriptions` (1 atau 12 bulan, diperpanjang dari `ends_at` jika masih aktif) dan `users.subscription_status='active'`; komisi afiliasi dicatat.
4. Order pending kedaluwarsa setelah `PAYMENT_EXPIRE_HOURS` (24 jam) — **catatan:** nilai ini terdefinisi di config tetapi belum ada job yang meng-expire order secara otomatis.

### 4.3b Pembayaran QRIS (SumoPod Managed Payment)
Metode kedua, di samping transfer manual. Aktif otomatis bila `SUMOPOD_PAY_API_KEY` terisi (tombol pilihan metode muncul di halaman `/payment/{id}`).

1. `POST /payment/{id}` dengan `metode=qris` → order `pending` (`payment_method='qris'`, `gateway_order_id='RL-xxxx'`, tanpa kode unik) → [SumopodPayService](app/Services/SumopodPayService.php) `POST {base}/api/v1/payments` (header `X-Api-Key`, `payment_method_type_code=qris`, kedaluwarsa maks. 24 jam) → `payment_id`, `payment_link_url`, `payment_expires_at` disimpan → user di-redirect ke halaman bayar SumoPod. Submit ulang memakai order yang sama (tanpa payment ganda). API gagal → order `cancel` + pesan error.
2. SumoPod memanggil **`POST /webhook/sumopod`** ([SumopodWebhookController](app/Http/Controllers/SumopodWebhookController.php)). Dikecualikan dari CSRF, diamankan **signature Svix** (HMAC-SHA256 atas `{svix-id}.{svix-timestamp}.{raw body}` dengan `SUMOPOD_PAY_WEBHOOK_SECRET`, toleransi waktu 5 menit, perbandingan constant-time). Signature salah → 401.
3. `payment.completed` → nominal harus sama dengan order, lalu [OrderSettlementService](app/Services/OrderSettlementService.php) melunasi order (idempoten, dengan row lock): membuka akses fitur / mengaktifkan langganan dan mencatat komisi afiliasi. `payment.failed` → `cancel`; `payment.expired` → `expire`; `payment.test` dan order tak dikenal → 200 (dicatat di log).
4. Konfirmasi admin untuk transfer manual kini memakai `OrderSettlementService` yang sama.

**Sandbox vs Live:** key Sandbox hanya berlaku di host sandbox. Untuk uji, set `SUMOPOD_PAY_BASE_URL=https://api-pay-sandbox.sumopod.com` (hasil uji 10 Nov 2026: key Sandbox di host Live → `401 Unauthorized`; di host Sandbox → payment berhasil dibuat). Untuk produksi: `SUMOPOD_PAY_BASE_URL=https://api-pay.sumopod.com` + key Live, lalu `php artisan config:cache`.

**Harga uji (sementara):** `PAYMENT_TEST_PRICE` + `PAYMENT_TEST_EMAIL` di `.env` menurunkan harga **hanya untuk satu email terverifikasi** (untuk tes transaksi Live bernominal kecil). Pengguna lain tetap membayar harga normal. Setelah pengujian, **kosongkan kedua variabel** lalu `php artisan config:cache`.

Biaya QRIS menurut dashboard SumoPod: 0,7% + Rp300 (dipotong dari saldo merchant), dana cair T+2. Kolom baru `orders.payment_id` (unik), `payment_link_url`, `payment_expires_at` (migration `2026_11_10_150000_...`).

### 4.4 Program afiliasi
- Afiliator **didaftarkan admin** (tanpa self-signup): kode referral unik, komisi default 20%.
- Link `?ref=KODE` ditangkap `CaptureReferral` (first-touch, di session). Saat `User` pertama dibuat, `referred_by_affiliate_id` diisi **permanen**.
- Tiap order `settlement` mencatat komisi `unpaid` di `affiliate_commissions` (satu per order, idempoten). Afiliator nonaktif tidak dapat komisi baru.
- Admin menandai lunas semua komisi `unpaid` seorang afiliator setelah transfer manual.

### 4.5 Wilayah
Dropdown berjenjang provinsi → kota → kecamatan → kelurahan lewat `/api/wilayah/*`. Provinsi dan kota di-seed; kecamatan/kelurahan **di-seed lazy** saat pertama kali diminta (`WilayahController::seedKecamatan/seedKelurahan`).

### 4.6 Lokalisasi
`SetLocale` membaca `session('locale')` (default `id`); ganti bahasa lewat `/lokal/{id|en}`. File terjemahan di [lang/](lang/). Laporan AI disimpan per `locale`.

---

## 5. Rute

| Area | Rute |
|---|---|
| Publik | `/`, `/mulai-analisis`, `POST /proses`, `/hasil`, `/profil-status` (AJAX), `/analisis` (katalog), `/lokal/{locale}`, `/tarot/pilih`, `/health` |
| Auth | `/login`, `POST /login`, `POST /logout` |
| OTP | `POST /otp/kirim`, `POST /otp/verifikasi` |
| Laporan | `/laporan/{id}` (form), `POST`, `/laporan/{id}/status` (polling), `/laporan/{id}/hasil` — throttle 10 req/menit |
| Payment | `GET/POST /payment/{id}` (field `metode`: `manual` \| `qris`), `POST /webhook/sumopod` (id numerik atau `langganan`, `langganan-bulanan`, `langganan-tahunan`) |
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

Kolom khusus: `orders.unique_code / transfer_amount / bank_tujuan / gateway_order_id (MANUAL-xxxx)`, `ai_reports.data_hash / locale`, `users.referred_by_affiliate_id`.

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
| Mail | `MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_*` |
| Gemini | `GEMINI_API_KEY, GEMINI_MODEL, GEMINI_MAX_OUTPUT_TOKENS` |
| Pembayaran | `PAYMENT_BCA_NAMA/NOMOR, PAYMENT_CIMB_NAMA/NOMOR, PAYMENT_EXPIRE_HOURS, PAYMENT_LANGGANAN_BULANAN_HARGA, PAYMENT_LANGGANAN_TAHUNAN_HARGA` |
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

Perintah artisan kustom: `hoki:kirim`, `subscription:sync`.

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
2. **Rekening bank** dipindah ke `.env` (`PAYMENT_BCA_NAMA/NOMOR`, `PAYMENT_CIMB_NAMA/NOMOR`); `config/payment.php` tidak lagi punya default. **Catatan:** nomor lama tetap ada di riwayat git (commit sebelumnya) — menghapusnya butuh menulis ulang riwayat, belum dilakukan.
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
