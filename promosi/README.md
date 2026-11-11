# Paket Video Promosi Rumus Langit

13 prompt video (satu video untuk setiap laporan), masing-masing 58 detik, **rasio 4:5 (1080×1350 px, 30 fps, MP4 H.264) — standar posting feed Instagram**.

**Pesan payung semua video:** *Rumus Langit — Solusi untuk setiap persimpangan hidup.* Setiap video mengangkat satu kasus nyata sehari-hari, memperlihatkan isi laporan yang menjawabnya, lalu mengarahkan penonton mencoba gratis di **rumuslangit.biz.id**.

## Daftar video

| No | Laporan | Hub | Tokoh & kasus | Berkas |
|---|---|---|---|---|
| 00 | Profil Karakter (Gratis) | Pintu masuk gratis | Rani — Kebingungan arah hidup: banyak tawaran dan banyak opini orang | [`00-profil-karakter.md`](video/00-profil-karakter.md) |
| 01 | Arah Mata Angin Rezeki | Wealth Hub | Pak Anto — Rezeki terasa macet | [`01-arah-mata-angin-rezeki.md`](video/01-arah-mata-angin-rezeki.md) |
| 02 | Potensi Bisnis & Gaya Investasi | Wealth Hub | Dina — Punya modal yang belum diputar | [`02-potensi-bisnis-gaya-investasi.md`](video/02-potensi-bisnis-gaya-investasi.md) |
| 03 | Hari Baik & Momentum Usaha | Wealth Hub | Bayu — Sudah menunda peluncuran berkali-kali karena bingung memilih hari dan waktu yang tepat | [`03-hari-baik-momentum-usaha.md`](video/03-hari-baik-momentum-usaha.md) |
| 04 | Arah Tempat Usaha & Ruko | Wealth Hub | Bu Sari — Tokonya sepi | [`04-arah-tempat-usaha-ruko.md`](video/04-arah-tempat-usaha-ruko.md) |
| 05 | Kalkulator Jodoh & Asmara Mendalam | Social Hub | Raka (30) dan Nia (28) — Mereka sering bertengkar karena hal sepele | [`05-kalkulator-jodoh-asmara.md`](video/05-kalkulator-jodoh-asmara.md) |
| 06 | Radar Toksisitas & Politik Kantor | Social Hub | Arif — Kontribusinya tidak dihargai | [`06-radar-toksisitas-politik-kantor.md`](video/06-radar-toksisitas-politik-kantor.md) |
| 07 | Karakter Anak & Pola Asuh | Social Hub | Bu Maya — Anaknya sulit diatur dan lebih banyak bermain gadget | [`07-karakter-anak-pola-asuh.md`](video/07-karakter-anak-pola-asuh.md) |
| 08 | Audit & Analisis Nama | Personal Wellness Hub | Sinta — Merasa hidupnya selalu terasa berat dan ragu apakah namanya selaras | [`08-audit-analisis-nama.md`](video/08-audit-analisis-nama.md) |
| 09 | Kesehatan & Vitalitas Energi Tubuh | Personal Wellness Hub | Hendra — Insomnia | [`09-kesehatan-vitalitas-energi.md`](video/09-kesehatan-vitalitas-energi.md) |
| 10 | Panduan Ruwat & Mitigasi Sial | Personal Wellness Hub | Wulan — Kehilangan pekerjaan lalu berpisah dari kekasih | [`10-ruwat-mitigasi-sial.md`](video/10-ruwat-mitigasi-sial.md) |
| 11 | Angka & Warna Keberuntungan Harian | Personal Wellness Hub | Tika — Bingung memilih warna pakaian untuk acara penting dan ingin rasa percaya dirinya terjaga sepanjang hari | [`11-angka-warna-keberuntungan-harian.md`](video/11-angka-warna-keberuntungan-harian.md) |
| 12 | Tafsir Mimpi | Traffic Driver (gratis) | Dimas — Terbangun cemas karena mimpi gigi copot | [`12-tafsir-mimpi.md`](video/12-tafsir-mimpi.md) |

## Struktur tetap tiap video (58 detik)

| Adegan | Waktu | Fungsi |
|---|---|---|
| S1 | 0:00–0:05 | Hook |
| S2 | 0:05–0:14 | Masalah / kasus |
| S3 | 0:14–0:20 | Titik balik: menemukan Rumus Langit |
| S4 | 0:20–0:27 | Cara pakai (isi data) |
| S5 | 0:27–0:37 | Laporan terbuka — inti fitur |
| S6 | 0:37–0:45 | Langkah nyata dari laporan |
| S7 | 0:45–0:51 | Transformasi |
| S8 | 0:51–0:58 | Penutup + CTA |
## Ukuran & ekspor

- **Utama: 4:5 — 1080×1350 px**, 30 fps, MP4 (H.264), audio AAC, ±60 detik. Ukuran vertikal terbesar yang tampil penuh di feed Instagram.
- Komposisi aman dipotong **1:1** dan 3:4 (kisi profil Instagram menampilkan bagian tengah): subjek dan teks selalu di tengah.
- Bila generator tidak punya pilihan 4:5: buat klip **9:16 (1080×1920)** lalu *crop* tengah ke 4:5 di editor. Untuk Reels, ekspor versi 9:16 dari sumber yang sama dan geser teks ke area aman Reels.

## Konsistensi karakter

Tiap berkas punya bagian **Kunci konsistensi karakter**: deskripsi tokoh yang baku (wajah, rambut, pakaian), prompt *character sheet*, dan aturan pemakaian (gambar referensi, seed sama, blok kunci disalin persis di semua adegan, pakaian tetap). Lakukan sebelum membuat klip.

## Aset

- **Logo:** [`aset/logo-rumus-langit.png`](aset/logo-rumus-langit.png) — logo aplikasi dengan **tagline berbahasa Inggris sudah dihapus** (dipotong); hanya ikon dan tulisan RUMUS LANGIT yang tersisa. Sumber asli: `public/images/logo.png` (tidak diubah).
- **Palet merek:** biru malam `#060614`, ungu `#7c3aed`, emas `#f59e0b`, teal `#06b6d4`; font situs: Inter.

## Cara memakai

1. Buka satu berkas video, salin bagian **3. Gaya visual** dan **prompt visual** tiap adegan ke generator video Anda (satu klip per adegan; adegan yang lebih dari 8 detik boleh dipecah).
2. Isi **Negative prompt** (bagian 8) di kolom negatif.
3. Buat voice-over dari **Naskah utuh** (bagian 5) dengan suara narator Indonesia, atau rekam sendiri.
4. Susun semua klip di editor sesuai waktu, tambahkan **teks layar** dan **logo** (jangan dibuat oleh generator), tambah musik bebas hak cipta.
5. Untuk adegan S5–S6, paling meyakinkan memakai **rekaman layar aplikasi asli** dengan akun demo bernama fiktif; prompt visual adalah alternatif bila ingin tampilan sinematik.
6. Cek daftar di bagian 9 sebelum mengunggah.

## Aturan yang berlaku untuk semua video

- Jangan menyebut teknologi atau pihak ketiga di balik layanan di mana pun (suara, teks, caption, nama berkas).
- Jangan menjanjikan hasil pasti; layanan ini untuk hiburan dan refleksi diri.
- Pembayaran dan harga tidak disebut di video (harga bisa berubah). Ajak "coba gratis"; detail harga ada di situs.
- Tidak ada refund di layanan; hindari janji "uang kembali" dalam bentuk apa pun.

## Saran urutan unggah

Mulai dari yang paling luas relevansinya: **00 Profil Karakter (gratis)** → **12 Tafsir Mimpi (gratis)** → **01 Arah Rezeki** → **05 Jodoh** → **06 Kantor** → lalu sisanya. Dua video gratis dipakai sebagai pintu masuk; sisanya mengarah ke analisis berbayar.
