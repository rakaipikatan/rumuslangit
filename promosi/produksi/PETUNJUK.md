# Petunjuk merakit video (di laptop Windows)

## Sekali saja
1. Pasang **Python** dari https://www.python.org/downloads/ — saat memasang, **centang "Add python.exe to PATH"**.
2. Salin seluruh folder `promosi` ke laptop (mis. `C:\promosi`).

## Tes cepat tanpa klip (disarankan dulu)
1. Buat folder kosong `produksi\00` (di dalam folder `promosi`).
2. Buka Command Prompt di folder `promosi`, ketik: `rakit.bat 00 --pratinjau --izinkan-kosong`
3. Bila berhasil, muncul `hasil\00-profil-karakter.mp4`: adegan yang belum ada klipnya berupa kartu penanda, tetapi teks layar, logo, dan end card sudah terpasang. Itu tanda skrip jalan di laptop Anda.

## Merakit video sungguhan
1. Taruh klip dari generator di folder `produksi\NN\` (NN = nomor video, mis. `01`), dengan nama:
   `s1.mp4` `s2.mp4` … `s7.mp4`  (adegan S1–S7). `s8.mp4` opsional: bila tidak ada, end card dibuat otomatis (latar gradasi + logo + tagline + alamat situs).
   Gambar diam (`s3.png`) juga boleh; format lain: `.mov`, `.webm`.
2. **Voice-over bahasa Indonesia** — pilih salah satu:
   - **Otomatis (paling mudah):** jalankan `rakit.bat 01 --suara` (suara perempuan) atau `rakit.bat 01 --suara ardi` (laki-laki). Skrip membuat `v1.mp3 … v8.mp3` dari naskah tiap adegan lalu memasangnya pas di awal adegan; bila suara sedikit lebih panjang dari adegannya, otomatis dipercepat (maks. 1,4×). Perlu **internet**. Suara dibuat sekali; hapus `v*.mp3` bila ingin membuat ulang.
   - **Rekam sendiri / suara lain:** simpan sebagai `v1.mp3 … v8.mp3` (per adegan) atau satu berkas penuh. Berkas yang sudah ada tidak akan ditimpa.
   - Dua bentuk berkas yang dikenali:
   - satu berkas penuh `voice.mp3` (mulai 0,25 dtk), **atau**
   - per adegan `v1.mp3` … `v8.mp3` (mulai di awal tiap adegan).
   - Naskah yang diucapkan ada di berkas prompt bagian 5 (juga di `data\NN-slug.json`).
3. Musik latar (opsional): `musik.mp3` (bebas hak cipta). Otomatis mengecil saat ada suara dan menghilang halus di akhir.
4. Klik dua kali **`rakit.bat`** (merakit semua folder yang ada), atau `rakit.bat 01` untuk satu video. Tambah `--pratinjau` untuk versi cepat setengah resolusi.
5. Hasil: `hasil\NN-slug.mp4` — 1080×1350 (4:5), 30 fps, 58 detik, siap diunggah ke feed Instagram.

## Aturan otomatis yang dipasang skrip
- Klip lebih panjang dari adegannya dipotong; lebih pendek → frame terakhir ditahan.
- Klip dipotong tengah (crop) ke 4:5, jadi subjek harus di tengah (sudah diatur di prompt).
- Teks layar tiap adegan, logo kecil transparan di S2–S7, label "Contoh tampilan" di S5–S6, end card di S8 — semuanya dari `data\NN-slug.json` (bersumber dari berkas prompt).
- Jika tidak ada suara maupun musik, video tetap diberi jalur audio senyap agar bisa diunggah.

## Jika gagal
- "Python belum terpasang" → ulangi langkah pemasangan dan centang PATH.
- "Klip S3 belum ada" → periksa nama berkas (`s3.mp4`) atau pakai `--izinkan-kosong`.
- Pesan "FFmpeg gagal" → salin pesannya ke chat untuk dibantu.

## Alternatif: menjalankan di VPS (tidak disarankan sebagai cara utama)
VPS hanya punya RAM 1,9 GB (sisa ±700 MB) dan melayani situs; mengolah video bisa memperlambatnya. Bila terpaksa: jalankan **malam hari**, **satu video per kali**, selalu dengan `--hemat`, dan tutup program lain. Perlu memasang FFmpeg dan pustaka Python di server (`sudo apt install -y ffmpeg python3-venv`, lalu venv + `pip install pillow edge-tts`), dan klip diunggah/diunduh lewat VS Code (seret ke folder `produksi/NN`, klik kanan berkas di `hasil` → Download). Perintah: `nice -n 19 python rakit.py 01 --suara --hemat`.
