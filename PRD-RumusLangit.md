# Product Requirements Document (PRD)
# RumusLangit.biz.id — Platform SaaS Konsultan Metafisika & Wellness Berbasis AI

---

**Versi:** 1.1  
**Domain:** rumuslangit.biz.id  
**VPS:** genji · 43.133.140.131  
**Stack Utama:** Laravel 11 · PostgreSQL v16 · Gemini AI (Google) · Redis  
**Nameservers:** ns1.sumopod.com · ns2.sumopod.com  
**Diperbarui:** 2025  

---

## Daftar Isi

1. [Gambaran Produk & Visi](#1-gambaran-produk--visi)
2. [Infrastruktur & Konfigurasi Server](#2-infrastruktur--konfigurasi-server)
3. [Arsitektur Sistem & Tech Stack](#3-arsitektur-sistem--tech-stack)
4. [Alur Pengguna: Free Trial & OTP](#4-alur-pengguna-free-trial--otp)
5. [Fitur & Modul Produk](#5-fitur--modul-produk)
6. [Strategi Monetisasi & Pricing](#6-strategi-monetisasi--pricing)
7. [Integrasi Layanan Pihak Ketiga](#7-integrasi-layanan-pihak-ketiga)
8. [Database Schema](#8-database-schema)
9. [Milestone & Roadmap](#9-milestone--roadmap)
10. [Risiko & Mitigasi](#10-risiko--mitigasi)
11. [Catatan Tambahan & Environment Variables](#11-catatan-tambahan--environment-variables)

---

## 1. Gambaran Produk & Visi

### 1.1 Definisi Platform

**Rumus Langit** adalah platform SaaS (Software as a Service) **Konsultan Metafisika & Wellness Modern Berbasis AI**. Platform ini menggabungkan tiga pilar utama:

| Pilar | Deskripsi |
|---|---|
| **Presisi Data Tradisional** | Hitungan matematika pakem dari warisan budaya: Neptu Weton Jawa, Elemen Shio Tionghoa, dan derajat Astrologi Barat. Dihitung akurat oleh kode backend Laravel. |
| **Kondisi Psikologis Real** | Digali melalui kuesioner interaktif Ya/Tidak yang mencerminkan kondisi nyata pengguna (kecemasan, masalah kantor, dinamika hubungan). |
| **Generative AI (Gemini — Google)** | Gemini bertindak sebagai "pakar spiritual dan psikolog personal" yang merajut data kalkulasi dan jawaban kuesioner menjadi laporan naratif personal, empatik, dan solutif. |

Semua dikemas dalam antarmuka **Cosmic Dark Mode** yang estetik, bersih, dan mewah — memberikan pengalaman transaksional yang aman dan tepercaya.

### 1.2 Target Pengguna

- **Milenial & Gen Z Indonesia** (usia 18–40 tahun) yang melek digital dan terbuka pada konten spiritual modern
- **Pelaku UKM & Wirausaha** yang mencari panduan hari baik, arah usaha, dan tata letak tempat kerja
- **Orang Tua & Pasangan Muda** yang membutuhkan panduan parenting berbasis karakter dan analisis kecocokan hubungan

### 1.3 Proposisi Nilai Utama

- **AI-Powered Narasi Personal** — Gemini AI menghasilkan teks unik untuk setiap pengguna, bukan template generik
- **Data Kalkulasi Akurat** — Engine Weton, Shio, dan Zodiak dihitung murni oleh PHP, tidak bergantung AI untuk matematika
- **UX Premium & Tepercaya** — Desain cosmic dark mode yang membedakan dari situs ramalan biasa
- **Harga Merakyat** — Mulai Rp 19.000 per laporan, berlangganan Rp 15.000 per bulan

---

## 2. Infrastruktur & Konfigurasi Server

### 2.1 VPS — genji (Tencent Cloud Jakarta)

| Parameter | Nilai |
|---|---|
| **Nama VPS** | genji |
| **Operating System** | Ubuntu 24.04 LTS |
| **Spesifikasi** | 2 vCPU · 2 GB RAM · 40 GB SSD Storage |
| **Public IP Address** | `43.133.140.131` |
| **Private IP Address** | `10.11.3.226` |
| **Login Username** | `ubuntu` |
| **Login Password** | `<VPS_PASSWORD>` — simpan di password manager |
| **Region** | Jakarta (AP-Southeast) |
| **Firewall Ports** | 22 (SSH) · 80 (HTTP) · 443 (HTTPS) · 6379 (Redis, internal only) |

> **Keamanan:** Sangat disarankan menonaktifkan login password dan beralih ke SSH key pair setelah server pertama kali aktif. Jalankan `ssh-copy-id ubuntu@43.133.140.131` dari mesin lokal Anda.

### 2.2 Database — Sumobase PostgreSQL v16

| Parameter | Nilai |
|---|---|
| **Host** | `pgsql-dbas-jkt1-005.sumobase.my.id` |
| **Port** | `65436` |
| **Database Name** | `db156f31c1f4d29d21` |
| **Username** | `uq1D4PL4py4CC5blw` |
| **Password** | `<DB_PASSWORD>` — lihat file `.env` |
| **Connection String** | `postgresql://<DB_USERNAME>:<DB_PASSWORD>@pgsql-dbas-jkt1-005.sumobase.my.id:65436/db156f31c1f4d29d21` |
| **Versi** | PostgreSQL 16 |
| **SSL Mode** | `require` (wajib aktif untuk koneksi aman) |
| **Connection Pool** | Gunakan PgBouncer atau `DB::reconnect()` Laravel untuk efisiensi |

**Laravel `.env` snippet:**
```env
DB_CONNECTION=pgsql
DB_HOST=pgsql-dbas-jkt1-005.sumobase.my.id
DB_PORT=65436
DB_DATABASE=db156f31c1f4d29d21
DB_USERNAME=uq1D4PL4py4CC5blw
DB_PASSWORD=<DB_PASSWORD>
DB_SSLMODE=require
```

### 2.3 Domain & DNS

| Parameter | Nilai |
|---|---|
| **Domain** | `rumuslangit.biz.id` |
| **Nameserver 1** | `ns1.sumopod.com` |
| **Nameserver 2** | `ns2.sumopod.com` |
| **DNS Record A** | `@ → 43.133.140.131` |
| **DNS Record A** | `www → 43.133.140.131` |
| **SSL/TLS** | Let's Encrypt via Certbot (auto-renew setiap 90 hari) |
| **Web Server** | Nginx sebagai reverse proxy ke Laravel PHP-FPM |

### 2.4 Software Wajib di VPS

```bash
# Runtime
PHP 8.3-FPM
Composer 2.x
Laravel 11.x
Node.js 20 LTS + npm

# Web & Cache
Nginx 1.26+
Redis 7.x
Supervisor (Queue Worker)

# Tools
Certbot (SSL Let's Encrypt)
Git
UFW Firewall
```

---

## 3. Arsitektur Sistem & Tech Stack

### 3.1 Layer Overview

```
[Browser/Mobile] 
    ↓ HTTPS
[Nginx Reverse Proxy — 43.133.140.131]
    ↓
[Laravel 11 (PHP-FPM)]
    ├── Kalkulasi Engine (Weton / Shio / Zodiak) — Pure PHP
    ├── Queue: GenerateAIReport → Redis → Laravel Horizon
    ├── HTTP Client → Gemini API (generativelanguage.googleapis.com)
    ├── Payment: Transfer manual + kode unik (MVP) → rencana webhook Midtrans/Xendit
    └── WA API Client (Fonnte)
         ↓
[PostgreSQL v16 — Sumobase]     [Redis 7 — VPS Lokal]
```

### 3.2 Stack Lengkap

| Layer | Teknologi |
|---|---|
| **Frontend** | Blade Templating + Alpine.js + Tailwind CSS (Cosmic Dark Theme) |
| **Chart** | Chart.js — radar chart energi harian 5 dimensi |
| **Backend** | Laravel 11 (PHP 8.3) |
| **AI Integration** | Google Gemini API via Laravel HTTP Client |
| **Queue & Cache** | Redis + Laravel Horizon |
| **Database** | PostgreSQL v16 (Sumobase) |
| **PDF** | `barryvdh/laravel-dompdf` |
| **Payment** | Transfer bank manual + kode unik 3 digit (MVP) · rencana Fase 3: Midtrans Snap API atau Xendit |
| **WhatsApp** | Fonnte API |
| **Scheduler** | Laravel Scheduler (cron every minute) |

### 3.3 Alur Gemini AI

1. User menyelesaikan kuesioner 5 pertanyaan Ya/Tidak
2. Laravel membangun **prompt dinamis**: System Instruction (persona psikolog spiritual Bahasa Indonesia) + User Prompt (data kalkulasi terformat JSON + jawaban kuesioner)
3. Job `GenerateAIReport` masuk ke Redis queue
4. Laravel Horizon memproses job secara async
5. Response Gemini di-cache di Redis dengan key `gemini:{feature_id}:{data_hash}` (TTL 24 jam)
6. Frontend polling status via AJAX setiap 2 detik → tampilkan hasil

### 3.4 Gemini API — Konfigurasi Project

| Parameter | Nilai |
|---|---|
| **Project Name** | `rumuslangit` |
| **Google Cloud Project Name** | `projects/933173718608` |
| **Project Number** | `933173718608` |
| **API Key** | `<GEMINI_API_KEY>` — lihat file `.env` |
| **Model yang Digunakan** | `gemini-2.5-flash` (cepat & hemat biaya) atau `gemini-2.5-pro` (lebih dalam) |
| **Endpoint** | `https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent` |

---

## 4. Alur Pengguna: Free Trial & OTP

### 4.1 Input Data Awal (Tanpa Login)

| Field | Tipe | Keterangan |
|---|---|---|
| Nama Lengkap | String | Wajib diisi |
| Tanggal Lahir | Date Picker | Terpisah: tanggal, bulan, tahun |
| Jam Lahir | Dropdown | Per jam (00.00–23.00), wajib untuk akurasi zodiak |
| Provinsi | Dropdown AJAX | Sumber: API Wilayah Indonesia |
| Kecamatan | Dropdown AJAX | Dinamis berdasarkan Provinsi yang dipilih |
| Kelurahan | Dropdown AJAX | Dinamis berdasarkan Kecamatan yang dipilih |

### 4.2 Hasil Gratis yang Ditampilkan

1. **Profil Karakter Gabungan** — ringkasan perpaduan Shio, Zodiak, dan Weton lahir dalam satu paragraf naratif
2. **Radar Energi Harian** — Chart.js radar chart 5 dimensi: Karir, Asmara, Cuan, Kesehatan, Spiritual (skor 0–100)
3. **Audit Energi Wilayah** — kesesuaian elemen kelurahan vs elemen lahir (badge: Selaras / Netral / Kurang Selaras)

### 4.3 Proteksi Trial via WhatsApp OTP (Fonnte)

**Alur:**
1. User klik "Akses Analisis Lebih Dalam"
2. Pop-up muncul: _"Verifikasi Nomor WhatsApp untuk Menyimpan Profil & Membuka Akses Lanjutan"_
3. Laravel kirim kode OTP 6 digit via Fonnte API (TTL: 5 menit, maks 3 percobaan)
4. Setelah verifikasi berhasil, kolom `phone_hash` + `trial_used_at` pada tabel `phone_verifications` dikunci
5. Percobaan mendaftar ulang dengan nomor yang sama ditolak dengan pesan: _"Nomor ini telah mencapai batas maksimum penggunaan trial harian."_

**Konfigurasi Fonnte:**

| Parameter | Nilai |
|---|---|
| **API Endpoint** | `https://api.fonnte.com/send` |
| **Token** | `<FONNTE_TOKEN>` — lihat file `.env` |
| **Format Pesan OTP** | `Kode verifikasi Rumus Langit Anda: {OTP}. Berlaku 5 menit. Jangan bagikan ke siapapun.` |

**Tambahan proteksi (opsional — Fase 4):** fingerprint browser (`device_id`) sebagai lapisan kedua di atas OTP untuk mencegah abuse dari nomor HP berbeda di perangkat yang sama.

---

## 5. Fitur & Modul Produk

### A. Wealth Hub (Finansial & Bisnis)

#### A1. Arah Mata Angin Rezeki — Rp 19.000–29.000

**Deskripsi:** Analisis arah kompas terbaik berdasarkan elemen lahir dikombinasikan dengan kondisi aliran rezeki saat ini.

**Kuesioner (5 pertanyaan Ya/Tidak):**
1. Apakah akhir-akhir ini Anda merasa rezeki Anda sering macet atau tertahan?
2. Apakah Anda sering melakukan negosiasi bisnis di luar kantor atau rumah?
3. Apakah Anda merasa posisi duduk atau tempat kerja Anda sekarang menghadap ke tembok atau membelakangi jalan?
4. Apakah Anda berencana mencari peluang rezeki di luar kota kelurahan tempat tinggal Anda saat ini?
5. Apakah waktu produktif Anda lebih banyak di malam hari daripada siang hari?

---

#### A2. Potensi Bisnis & Gaya Investasi — Rp 19.000–29.000

**Deskripsi:** Profil risiko investasi dan rekomendasi sektor bisnis berdasarkan elemen shio dan psikologi finansial dari kuesioner.

**Kuesioner (5 pertanyaan Ya/Tidak):**
1. Apakah Anda lebih suka keuntungan kecil yang pasti daripada keuntungan besar tapi berisiko?
2. Apakah Anda pernah mengalami kerugian besar (boncos) dalam satu tahun terakhir?
3. Apakah saat ini Anda memiliki modal menganggur (idle fund) yang siap diputar?
4. Apakah Anda tertarik pada bisnis yang melibatkan aset fisik seperti properti atau barang dagangan nyata?
5. Apakah Anda sering mengambil keputusan finansial hanya berdasarkan ikut-ikutan tren (FOMO)?

---

#### A3. Hari Baik & Momentum Usaha — Rp 19.000–29.000

**Deskripsi:** Kalkulasi hari baik 3 bulan ke depan berdasarkan Neptu Weton dan kondisi proyek. Output: kalender mini dengan penanda hari hijau/kuning/merah.

**Kuesioner (5 pertanyaan Ya/Tidak):**
1. Apakah Anda berencana mengeksekusi proyek atau membuka usaha dalam waktu tiga bulan ke depan?
2. Apakah bisnis yang akan Anda jalankan ini melibatkan kerja sama dengan mitra atau partner?
3. Apakah Anda percaya bahwa pemilihan hari yang salah bisa memengaruhi kelancaran usaha?
4. Apakah modal utama usaha ini murni dari dana pribadi Anda sendiri?
5. Apakah Anda sedang terikat kontrak aktif yang belum selesai dengan pihak lain?

---

#### A4. Arah Tempat Usaha & Ruko — Rp 19.000–29.000

**Deskripsi:** Analisis feng shui modern — posisi meja, kasir, pintu, dan rekomendasi tata letak berdasarkan elemen lahir pemilik.

**Kuesioner (5 pertanyaan Ya/Tidak):**
1. Apakah tempat usaha atau ruko Anda saat ini posisinya berada di tusuk sate atau tikungan jalan?
2. Apakah Anda sering merasa suasana di dalam tempat kerja atau toko terasa panas atau bikin tidak betah?
3. Apakah posisi meja kasir atau meja kerja Anda langsung berhadapan dengan pintu masuk utama?
4. Apakah ruko atau tempat usaha Anda saat ini statusnya adalah sewa atau kontrak (bukan milik sendiri)?
5. Apakah Anda ingin mengubah tata letak interior toko Anda dalam waktu dekat demi menarik pelanggan?

---

### B. Social Hub (Relasi & Hubungan)

#### B5. Kalkulator Jodoh & Asmara Mendalam — Rp 19.000–29.000

**Deskripsi:** Kompatibilitas elemen pasangan dan analisis psikologi hubungan. Gemini menghasilkan narasi empatik tentang dinamika pasangan beserta panduan komunikasi.

**Kuesioner (5 pertanyaan Ya/Tidak):**
1. Apakah saat ini hubungan Anda dan pasangan sedang sering diwarnai cekcok karena hal sepele?
2. Apakah Anda merasa ada pihak keluarga seperti orang tua atau saudara yang kurang merestui hubungan ini?
3. Apakah Anda dan pasangan memiliki perbedaan sifat yang sangat bertolak belakang?
4. Apakah Anda berencana membawa hubungan ini ke jenjang pernikahan dalam waktu dekat?
5. Apakah Anda sering merasa cemas atau tidak aman (insecure) terhadap kesetiaan pasangan Anda?

---

#### B6. Radar Toksisitas & Politik Kantor — Rp 19.000–29.000

**Deskripsi:** Profil karir vs lingkungan kerja saat ini. Gemini menghasilkan strategi menghadapi rekan toksik dan timing tepat untuk keputusan karir besar.

**Kuesioner (5 pertanyaan Ya/Tidak):**
1. Apakah Anda merasa kontribusi kerja Anda di kantor sering tidak dihargai oleh atasan?
2. Apakah suasana di lingkungan kerja Anda saat ini terasa kompetitif secara tidak sehat (toxic)?
3. Apakah Anda sering merasa cemas atau stres setiap kali hari Senin tiba?
4. Apakah Anda berencana untuk resign atau pindah divisi dalam waktu dekat?
5. Apakah saat ini Anda memiliki rekan kerja dekat yang benar-benar bisa dipercaya di kantor?

---

#### B7. Karakter Anak & Pola Asuh (Parenting Style) — Rp 19.000–29.000

**Deskripsi:** Analisis elemen lahir anak vs gaya pengasuhan orang tua. Rekomendasi komunikasi yang dipersonalisasi oleh Gemini.

**Kuesioner (5 pertanyaan Ya/Tidak):**
1. Apakah anak Anda sering menunjukkan sifat keras kepala atau sulit diatur belakangan ini?
2. Apakah Anda merasa kesulitan menebak bakat atau minat utama anak Anda saat ini?
3. Apakah anak Anda cenderung lebih pemalu dan tertutup saat berada di lingkungan baru?
4. Apakah Anda sering merasa kehabisan kesabaran saat mendampingi anak belajar di rumah?
5. Apakah Anda ingin menyesuaikan gaya berkomunikasi Anda agar anak lebih mendengarkan nasihat?

---

### C. Personal Wellness Hub (Proteksi & Diri)

#### C8. Audit & Analisis Nama — Rp 19.000–29.000

**Deskripsi:** Analisis numerologi nama dan elemen lahir. Saran inisial penyeimbang energi. Gemini menghasilkan interpretasi naratif yang personal.

**Kuesioner (5 pertanyaan Ya/Tidak):**
1. Apakah Anda merasa hidup Anda selalu terasa berat atau sial meskipun sudah bekerja keras?
2. Apakah nama yang Anda gunakan di dunia profesional berbeda dengan nama di akta kelahiran?
3. Apakah Anda memiliki rencana untuk membuat nama bisnis, nama pena, atau nama panggung baru?
4. Apakah Anda sering merasa nama Anda memiliki arti yang kurang selaras dengan doa orang tua?
5. Apakah Anda ingin mengetahui inisial huruf tambahan yang bisa menyeimbangkan energi nama Anda?

---

#### C9. Kesehatan & Vitalitas Energi Tubuh — Rp 19.000–29.000

**Deskripsi:** Pemetaan zona tubuh lemah berdasarkan elemen lahir beserta panduan gaya hidup yang selaras dengan ritme energi personal.

**Kuesioner (5 pertanyaan Ya/Tidak):**
1. Apakah Anda sering mengalami gangguan tidur (insomnia) atau sering terbangun di malam hari?
2. Apakah area pundak, leher, atau kepala Anda sering terasa tegang dan pegal tanpa alasan medis yang jelas?
3. Apakah Anda merasa energi dan stamina Anda cepat habis padahal aktivitas tidak terlalu padat?
4. Apakah Anda sedang menjalani program diet atau pemulihan kesehatan saat ini?
5. Apakah suasana hati (mood) Anda sering berubah drastis secara tiba-tiba dalam sehari?

---

#### C10. Panduan Ruwat & Mitigasi Sial (Tolak Bala) — Rp 19.000–29.000

**Deskripsi:** Deteksi posisi Ciong tahun berjalan dan panduan doa/ritual tolak bala. Output menyertakan kalender hari naas bulan ini.

**Kuesioner (5 pertanyaan Ya/Tidak):**
1. Apakah Anda tahu bahwa Shio atau Zodiak Anda sedang mengalami posisi Ciong (kurang beruntung) tahun ini?
2. Apakah dalam kurun waktu tiga bulan terakhir Anda berturut-turut mengalami kesialan nyata?
3. Apakah Anda merasa ada energi negatif atau beban tak kasat mata yang menyelimuti pikiran Anda?
4. Apakah Anda bersedia meluangkan waktu sepuluh hingga lima belas menit sehari untuk melakukan meditasi mandiri?
5. Apakah Anda ingin tahu hari naas Anda di bulan ini agar bisa menunda keputusan-keputusan besar?

---

#### C11. Generator Angka & Warna Keberuntungan Harian — Khusus Subscriber

**Deskripsi:** Output harian berupa 3 angka hoki + 2 warna busana + mantra hari. Dikirim otomatis ke WhatsApp subscriber aktif setiap pagi pukul 06.00 WIB via Laravel Scheduler + Fonnte.

**Kuesioner (5 pertanyaan Ya/Tidak):**
1. Apakah Anda sering kebingungan memilih warna pakaian saat ingin menghadiri acara penting?
2. Apakah Anda mempercayai bahwa angka tertentu memiliki getaran frekuensi hoki bagi hidup Anda?
3. Apakah Anda sering menggunakan kombinasi angka untuk keperluan penting seperti PIN atau plat nomor?
4. Apakah Anda ingin menerima pembaruan angka dan warna hoki ini setiap hari langsung di WhatsApp?
5. Apakah Anda merasa warna pakaian yang Anda gunakan hari ini memengaruhi tingkat rasa percaya diri Anda?

---

### D. The Traffic Driver

#### D12. Smart Search Library Tafsir Mimpi — Freemium → Paywall

**Deskripsi:** Satu input terbuka. Tafsir dasar dan angka keberuntungan diberikan gratis. Jika mimpi bermakna buruk, Gemini memunculkan ringkasan peringatan dan tombol paywall Paket Proteksi Diri.

**Input (1 pertanyaan terbuka):**
> _"Apa objek atau kejadian spesifik yang paling Anda ingat dari mimpi semalam? (Contoh: digigit ular, gigi copot, rumah kebakaran)"_

**Logika Monetisasi:**
- Mimpi netral/positif → tafsir lengkap + angka keberuntungan gratis
- Mimpi bermakna buruk → ringkasan peringatan singkat (gratis) + CTA tombol paywall: _"Dapatkan Panduan Doa & Ritual Netralisir Energi — Rp 19.000"_

---

## 6. Strategi Monetisasi & Pricing

### 6.1 Model Eceran / Per Laporan

> Psikologi harga di bawah Rp 30.000 terasa seperti "uang jajan" bagi milenial/Gen Z sehingga meminimalkan friction pembelian.

| Produk | Harga |
|---|---|
| Satu laporan fitur manapun (A1–D12) | **Rp 19.000 – Rp 29.000** |
| Pembayaran via | **MVP: Transfer bank manual (BCA/CIMB Niaga) + kode unik 3 digit** — lihat 7.2. Rencana Fase 3: QRIS, GoPay, OVO, Dana, LinkAja, Virtual Account via gateway |

### 6.2 Model Paket Bundling Premium (PDF Download)

| Paket | Fitur yang Termasuk | Harga |
|---|---|---|
| **Paket Sukses Finansial** | A1 + A2 + A3 + A4 (Wealth Hub) + PDF premium | **Rp 49.000** |
| **Paket Harmoni Keluarga & Kerja** | B5 + B6 + B7 (Social Hub) + PDF premium | **Rp 49.000** |
| **Paket Proteksi Diri & Nama** | C8 + C9 + C10 (Wellness Hub) + PDF premium | **Rp 39.000** |

### 6.3 Model Langganan (SaaS Subscription)

| Plan | Harga | Keuntungan |
|---|---|---|
| **Bulanan** | **Rp 15.000/bulan** | Akses tak terbatas semua Hub + Fitur C11 (hoki harian via WA) + Ringkasan Navigasi Rezeki Harian |
| **Tahunan** | **Rp 99.000/tahun** | Setara Rp 8.250/bulan · Hemat 45% vs bulanan · Badge "Member Tahunan" di profil |

---

## 7. Integrasi Layanan Pihak Ketiga

### 7.1 Fonnte — WhatsApp OTP & Notifikasi Harian

| Parameter | Nilai |
|---|---|
| **Vendor** | Fonnte |
| **API Endpoint** | `https://api.fonnte.com/send` |
| **Token** | `<FONNTE_TOKEN>` — lihat file `.env` |
| **Fungsi 1** | Kirim OTP 6 digit saat verifikasi registrasi (TTL 5 menit, maks 3 percobaan) |
| **Fungsi 2** | Kirim "Peta Hoki Harian" otomatis pukul 06.00 WIB ke semua subscriber aktif via Laravel Scheduler |
| **Format Header** | `Authorization: <FONNTE_TOKEN>` |

**Contoh payload kirim OTP:**
```json
{
  "target": "628xxxxxxxxxx",
  "message": "Kode verifikasi Rumus Langit Anda: 847291. Berlaku 5 menit. Jangan bagikan ke siapapun.",
  "countryCode": "62"
}
```

### 7.2 Payment — Transfer Manual + Kode Unik (MVP, aktif saat ini)

Sebelum integrasi payment gateway (Fase 3), pembayaran ditangani secara manual lewat transfer bank dengan kode unik 3 digit agar tiap transaksi bisa dicocokkan satu-satu dengan mutasi rekening, tanpa perlu Virtual Account.

| Parameter | Detail |
|---|---|
| **Rekening 1** | BCA — 6630676183 a/n Yohanes Sefrianto |
| **Rekening 2** | CIMB Niaga — 0702731304800 a/n Yohanes Sefrianto |
| **Kode Unik** | Angka acak 3 digit (1–999) ditambahkan ke harga fitur, mis. Rp 19.000 → **Rp 19.xxx**. Dijamin tidak bentrok dengan order `pending` lain yang harga dasarnya sama |
| **Alur** | User pilih rekening tujuan → sistem generate kode unik & buat `orders.status = pending` → user transfer nominal persis → admin cek mutasi rekening → admin klik **Konfirmasi** di Admin Panel (`/admin/orders`) → `orders.status = settlement` → paywall laporan terbuka |
| **Idempotency** | Satu order `pending` per user+fitur dipakai ulang (tidak generate kode unik baru tiap submit ulang) |
| **Implementasi** | `App\Http\Controllers\PaymentController`, kolom `orders.unique_code` / `orders.transfer_amount` / `orders.bank_tujuan`, rekening dikonfigurasi di `config/payment.php` |

### 7.3 Payment Gateway (Rencana Fase 3 — belum diimplementasikan)

| Parameter | Detail |
|---|---|
| **Vendor Utama** | Midtrans (Snap API) atau Xendit |
| **Channel Pembayaran** | QRIS, GoPay, OVO, Dana, LinkAja, VA BCA/BNI/Mandiri |
| **Webhook Endpoint** | `POST /webhook/payment` |
| **Alur Webhook** | Validasi signature → update `orders.status = settled` → unlock paywall laporan |
| **Idempotency** | Setiap `order_id` unik per transaksi. Cek duplikat sebelum memproses webhook |

### 7.4 Google Gemini AI

| Parameter | Nilai |
|---|---|
| **Project Name** | `rumuslangit` |
| **Google Cloud Project** | `projects/933173718608` |
| **Project Number** | `933173718608` |
| **API Key** | `<GEMINI_API_KEY>` — lihat file `.env` |
| **Model Utama** | `gemini-2.5-flash` (cepat, hemat biaya, cocok untuk production) |
| **Model Alternatif** | `gemini-1.5-pro` (lebih mendalam, untuk fitur premium) |
| **Endpoint** | `https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={API_KEY}` |
| **max_output_tokens** | `1500` per laporan (±800 kata narasi) |
| **Bahasa Output** | Bahasa Indonesia yang empatik, personal, dan solutif |

**Struktur Prompt Gemini:**

```
[System Instruction]
Kamu adalah Rumus Langit, seorang konsultan metafisika dan psikolog personal 
berbahasa Indonesia yang empatik, bijaksana, dan solutif. Gaya bahasamu hangat, 
personal, dan mengalir seperti surat dari sahabat yang memahami. 
Hindari bahasa klinis atau robotik. Selalu akhiri dengan satu kalimat afirmasi 
positif yang membangkitkan semangat pengguna.

[User Prompt]
Data Kelahiran: {json_kalkulasi_weton_shio_zodiak}
Fitur yang Dianalisis: {nama_fitur}
Jawaban Kuesioner: {json_jawaban_5_pertanyaan}
Lokasi Pengguna: {kelurahan}, {kecamatan}, {provinsi}

Hasilkan laporan naratif personal untuk fitur ini dalam format:
1. Pembukaan personal (2 kalimat, sapa pengguna dengan namanya)
2. Analisis utama (4–6 paragraf)
3. Rekomendasi konkret (3 poin)
4. Penutup & afirmasi positif
```

### 7.5 Redis — Cache & Queue

| Fungsi | Detail |
|---|---|
| **Cache Gemini Response** | Key: `gemini:{feature_id}:{data_hash}` · TTL: 24 jam · Mencegah tagihan API berganda untuk data identik |
| **Queue Driver** | Laravel Horizon memproses job `GenerateAIReport` secara async |
| **Session Store** | Redis untuk session pengguna (lebih cepat dari database session) |
| **Rate Limiter** | Redis throttle middleware membatasi panggilan Gemini API per user (maks 10 request/jam) |

### 7.6 PDF Generator

| Parameter | Detail |
|---|---|
| **Package** | `barryvdh/laravel-dompdf` |
| **Template** | Blade view khusus PDF dengan branding Rumus Langit, logo, dan skor radar |
| **Storage** | File disimpan di `storage/app/reports/` lalu signed URL dikirim ke user (TTL 1 jam) |

---

## 8. Database Schema

### Tabel Utama (PostgreSQL v16)

#### `users`
```sql
CREATE TABLE users (
    id                  BIGSERIAL PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,
    phone_hash          VARCHAR(64) UNIQUE,
    email               VARCHAR(255) UNIQUE,
    dob                 DATE NOT NULL,
    birth_hour          SMALLINT NOT NULL,            -- 0–23
    province            VARCHAR(100),
    kecamatan           VARCHAR(100),
    kelurahan           VARCHAR(100),
    subscription_status VARCHAR(20) DEFAULT 'free',   -- free | active | expired
    created_at          TIMESTAMP DEFAULT NOW(),
    updated_at          TIMESTAMP DEFAULT NOW()
);
```

#### `phone_verifications`
```sql
CREATE TABLE phone_verifications (
    id              BIGSERIAL PRIMARY KEY,
    phone_hash      VARCHAR(64) NOT NULL,
    otp_code        VARCHAR(6),
    attempts        SMALLINT DEFAULT 0,
    verified_at     TIMESTAMP,
    trial_used_at   TIMESTAMP,
    created_at      TIMESTAMP DEFAULT NOW()
);
```

#### `birth_profiles`
```sql
CREATE TABLE birth_profiles (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT REFERENCES users(id),
    weton_neptune   SMALLINT,
    shio            VARCHAR(20),
    zodiac_sign     VARCHAR(20),
    ascendant       VARCHAR(20),
    element         VARCHAR(20),
    calculated_at   TIMESTAMP DEFAULT NOW()
);
```

#### `questionnaire_sessions`
```sql
CREATE TABLE questionnaire_sessions (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT REFERENCES users(id),
    feature_id      SMALLINT NOT NULL,           -- 1–12
    answers         JSONB NOT NULL,              -- {"q1": true, "q2": false, ...}
    created_at      TIMESTAMP DEFAULT NOW()
);
```

#### `ai_reports`
```sql
CREATE TABLE ai_reports (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT REFERENCES users(id),
    feature_id      SMALLINT NOT NULL,
    data_hash       VARCHAR(64) NOT NULL,        -- SHA256 dari prompt input
    prompt_used     TEXT,
    response_text   TEXT,
    tokens_used     INT,
    cached_at       TIMESTAMP DEFAULT NOW()
);
```

#### `orders`
```sql
CREATE TABLE orders (
    id                  BIGSERIAL PRIMARY KEY,
    user_id             BIGINT REFERENCES users(id),
    feature_id          SMALLINT,
    package_id          SMALLINT,
    amount              INT NOT NULL,            -- harga dasar, dalam Rupiah
    unique_code         SMALLINT,                -- kode unik 3 digit (1-999) untuk transfer manual
    transfer_amount     INT,                     -- amount + unique_code = nominal yang harus ditransfer
    bank_tujuan         VARCHAR(30),              -- kode rekening tujuan, mis. 'bca' | 'cimb'
    payment_method      VARCHAR(50),
    gateway_order_id    VARCHAR(100) UNIQUE,
    status              VARCHAR(20) DEFAULT 'pending', -- pending | settlement | expired | cancel
    settled_at          TIMESTAMP,
    created_at          TIMESTAMP DEFAULT NOW()
);
```

#### `subscriptions`
```sql
CREATE TABLE subscriptions (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT REFERENCES users(id),
    plan            VARCHAR(10) NOT NULL,        -- monthly | yearly
    starts_at       TIMESTAMP NOT NULL,
    ends_at         TIMESTAMP NOT NULL,
    auto_renew      BOOLEAN DEFAULT TRUE,
    gateway_token   VARCHAR(255),
    created_at      TIMESTAMP DEFAULT NOW()
);
```

#### `pdf_downloads`
```sql
CREATE TABLE pdf_downloads (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT REFERENCES users(id),
    report_id       BIGINT REFERENCES ai_reports(id),
    file_path       VARCHAR(255),
    signed_url      TEXT,
    url_expires_at  TIMESTAMP,
    downloaded_at   TIMESTAMP,
    created_at      TIMESTAMP DEFAULT NOW()
);
```

---

## 9. Milestone & Roadmap

### Fase 1 — Fondasi (Minggu 1–3)

- [ ] Setup VPS genji: Nginx, PHP 8.3-FPM, Composer, Laravel install
- [ ] Koneksi PostgreSQL Sumobase (test koneksi + SSL verify)
- [ ] Instalasi Redis + konfigurasi Laravel Queue & Horizon
- [ ] Konfigurasi domain DNS + SSL Let's Encrypt
- [ ] Engine kalkulasi Weton / Shio / Zodiak (pure PHP, full test coverage)
- [ ] Seed data wilayah Indonesia (Provinsi, Kecamatan, Kelurahan)

### Fase 2 — Core MVP (Minggu 4–7)

- [ ] Halaman landing page + form input Free Trial
- [ ] Radar chart energi harian (Chart.js 5 dimensi)
- [ ] Integrasi Fonnte API untuk WhatsApp OTP
- [ ] Proteksi trial via `phone_hash` di PostgreSQL
- [ ] Integrasi Gemini API + prompt builder dinamis
- [ ] Laravel Horizon + job `GenerateAIReport` async
- [ ] 4 fitur Wealth Hub (A1–A4) dengan kuesioner dan paywall

### Fase 3 — Monetisasi Penuh (Minggu 8–10)

- [x] Payment MVP: transfer bank manual (BCA/CIMB Niaga) + kode unik 3 digit, konfirmasi via Admin Panel
- [ ] Integrasi Midtrans/Xendit (Snap API + webhook handler) — menggantikan konfirmasi manual
- [x] Sistem paywall: cek `orders.status = settled` → unlock laporan
- [ ] PDF export via DomPDF + signed URL download (TTL 1 jam)
- [ ] 3 fitur Social Hub (B5–B7)
- [ ] 4 fitur Personal Wellness Hub (C8–C11)
- [ ] Halaman profil pengguna + riwayat laporan

### Fase 4 — Growth & Automation (Minggu 11–14)

- [ ] Sistem langganan bulanan/tahunan (auto-renewal)
- [ ] Laravel Scheduler: kirim hoki harian pukul 06.00 WIB via Fonnte
- [ ] Fitur Traffic Driver: Tafsir Mimpi + paywall dinamis (D12)
- [ ] Redis cache Gemini responses (penghematan biaya API)
- [ ] Admin dashboard: monitoring revenue, token usage, active subscribers
- [ ] Load test + optimasi performa (target: 500 concurrent users di 2GB RAM)

---

## 10. Risiko & Mitigasi

| Level | Risiko | Mitigasi |
|---|---|---|
| 🔴 **Tinggi** | VPS 2GB RAM overload saat traffic spike | Redis cache agresif, batasi concurrent Horizon workers maks 3, pertimbangkan upgrade RAM ke 4GB saat MAU > 500 |
| 🔴 **Tinggi** | Gemini API down atau rate limit terlampaui | Fallback message UI yang informatif, retry logic 3x dengan exponential backoff, simpan semua prompt untuk retry manual via admin panel |
| 🟡 **Sedang** | Biaya API Gemini membengkak | Cache Redis per `data_hash` (TTL 24 jam), gunakan `gemini-2.5-flash` untuk produksi, monitor quota harian di Google Cloud Console |
| 🟡 **Sedang** | Koneksi PostgreSQL Sumobase putus | `DB::reconnect()` handler di Laravel, health check endpoint `/health`, monitoring uptime via UptimeRobot |
| 🟡 **Sedang** | Fonnte API gagal kirim OTP | Retry otomatis 2x, fallback pesan error yang jelas ke user, log semua kegagalan pengiriman |
| 🟡 **Sedang** | Abuse trial via nomor HP baru terus | Tambahkan fingerprint browser (`device_id`) sebagai lapisan kedua di atas OTP WA |
| 🟡 **Sedang** | Transfer manual telat/tidak diverifikasi admin | SLA verifikasi maks 1x24 jam, order `pending` tampil di Admin Panel (`/admin/orders`) dengan kode unik untuk pencocokan cepat |
| 🟢 **Rendah** | Setelah migrasi ke gateway: pembayaran gagal / webhook tidak masuk | Cron job cek status payment setiap 15 menit untuk order `pending` > 30 menit |
| 🟢 **Rendah** | SSL kadaluarsa | Certbot auto-renew via cron (`0 0 * * * certbot renew`), alert email 30 hari sebelum expired |
| 🟢 **Rendah** | API key Gemini atau token Fonnte bocor | Simpan hanya di `.env`, pastikan `.env` ada di `.gitignore`, rotate key segera jika terjadi kebocoran |

---

## 11. Catatan Tambahan & Environment Variables

### 11.1 Template `.env` Lengkap (Isi nilai asli — JANGAN commit ke Git)

```env
# =============================================
# APP
# =============================================
APP_NAME="Rumus Langit"
APP_URL=https://rumuslangit.biz.id
APP_ENV=production
APP_DEBUG=false
APP_KEY=                          # generate: php artisan key:generate

# =============================================
# DATABASE — Sumobase PostgreSQL v16
# =============================================
DB_CONNECTION=pgsql
DB_HOST=pgsql-dbas-jkt1-005.sumobase.my.id
DB_PORT=65436
DB_DATABASE=db156f31c1f4d29d21
DB_USERNAME=uq1D4PL4py4CC5blw
DB_PASSWORD=<DB_PASSWORD>
DB_SSLMODE=require

# =============================================
# CACHE & QUEUE — Redis (VPS Lokal)
# =============================================
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# =============================================
# AI — Google Gemini
# =============================================
GEMINI_API_KEY=<GEMINI_API_KEY>
GEMINI_MODEL=gemini-2.5-flash
GEMINI_MAX_OUTPUT_TOKENS=1500
GEMINI_PROJECT_NUMBER=933173718608

# =============================================
# WHATSAPP — Fonnte
# =============================================
FONNTE_TOKEN=<FONNTE_TOKEN>
FONNTE_API_URL=https://api.fonnte.com/send

# =============================================
# PAYMENT GATEWAY — Midtrans
# =============================================
MIDTRANS_SERVER_KEY=<MIDTRANS_SERVER_KEY>
MIDTRANS_CLIENT_KEY=<MIDTRANS_CLIENT_KEY>
MIDTRANS_IS_PRODUCTION=true
MIDTRANS_SNAP_URL=https://app.midtrans.com/snap/snap.js
```

> **Penting:** Pastikan `.env` selalu ada di `.gitignore`. Jangan pernah commit file ini ke repository manapun.

### 11.2 Perintah Setup Awal VPS genji

```bash
# SSH ke server
ssh ubuntu@43.133.140.131

# Update sistem
sudo apt update && sudo apt upgrade -y

# Install PHP 8.3
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install php8.3-fpm php8.3-pgsql php8.3-redis php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath -y

# Install Nginx & Redis
sudo apt install nginx redis-server -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs -y

# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Setup UFW Firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Deploy Laravel
cd /var/www
sudo composer create-project laravel/laravel rumuslangit
cd rumuslangit
sudo chown -R ubuntu:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
cp .env.example .env
php artisan key:generate

# SSL
sudo certbot --nginx -d rumuslangit.biz.id -d www.rumuslangit.biz.id

# Install Laravel Horizon
composer require laravel/horizon
php artisan horizon:install

# Install DomPDF
composer require barryvdh/laravel-dompdf
```

### 11.3 Konfigurasi Nginx untuk Laravel

```nginx
server {
    listen 80;
    server_name rumuslangit.biz.id www.rumuslangit.biz.id;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name rumuslangit.biz.id www.rumuslangit.biz.id;

    root /var/www/rumuslangit/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/rumuslangit.biz.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/rumuslangit.biz.id/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 11.4 Supervisor untuk Laravel Horizon

```ini
[program:horizon]
process_name=%(program_name)s
command=php /var/www/rumuslangit/artisan horizon
autostart=true
autorestart=true
user=ubuntu
redirect_stderr=true
stdout_logfile=/var/www/rumuslangit/storage/logs/horizon.log
```

---

*PRD RumusLangit v1.1 — rumuslangit.biz.id · VPS genji (43.133.140.131) · Jakarta*  
*AI: Google Gemini · Database: PostgreSQL v16 Sumobase · WhatsApp: Fonnte*
