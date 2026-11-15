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
   **Klip generator lebih pendek dari adegannya?** Beri nama bertingkat `s2a.mp4`, `s2b.mp4`, … — skrip menyambungnya berurutan lalu menyesuaikan ke durasi adegan (kelebihan dipotong, kekurangan menahan bingkai terakhir). Jangan menghapus watermark generator; bila watermark mengganggu, gunakan paket berbayar tanpa watermark.
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

## Memilih mesin suara yang lebih natural
`--suara` memakai **Edge TTS** (gratis, hanya dua suara Indonesia: `gadis` perempuan, `ardi` laki-laki). Untuk suara yang lebih natural ada dua mesin berbayar/berkunci, dipilih dengan `--mesin`:
- `--mesin gemini` (kunci di variabel `GEMINI_API_KEY`; nama suara dengan `--nama-suara`, mis. `Kore`, `Puck`, `Aoede`, `Charon`; model bisa diganti lewat `GEMINI_TTS_MODEL`, gaya bicara lewat `GEMINI_TTS_ARAHAN`).
- `--mesin elevenlabs` (kunci di `ELEVENLABS_API_KEY`; ID suara dari akun Anda lewat `--nama-suara` atau `ELEVENLABS_VOICE_ID`).

**Kunci API jangan pernah ditulis di berkas atau dikirim ke chat.** Di jendela cmd, ketik `set GEMINI_API_KEY=kunci_Anda` (hanya berlaku di jendela itu).

**Bandingkan dulu** dengan satu kalimat sama (tidak merakit video):
```
venv\Scripts\python.exe rakit.py --uji-suara --mesin edge --kecepatan 0
venv\Scripts\python.exe rakit.py --uji-suara --mesin gemini --nama-suara Kore
```
Hasil: `hasil\contoh-suara-<mesin>.mp3` atau `.wav`. Kalimat sendiri: `--uji-suara "kalimat Anda"`.

Bila cocok: `rakit.py 00 --suara --mesin gemini --nama-suara Kore` (suara dibuat per adegan; hapus `v*.mp3`/`v*.wav` lama bila ingin diganti).
