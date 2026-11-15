# Panduan edge-tts (rany2/edge-tts) — untuk dipakai di laptop sendiri

Repositori: https://github.com/rany2/edge-tts

`edge-tts` adalah paket Python dan perintah (CLI) untuk membuat suara dari teks memakai **suara neural Microsoft Edge**, tanpa kunci API dan tanpa membuka Edge. Dokumen ini adalah ringkasan agar bisa dipakai mandiri di laptop (tanpa VPS), lengkap dengan langkah untuk lingkungan Windows pemilik (Python mandiri yang dikelola `uv`, memakai `venv`).

> **Tanda sumber.** Bagian bertanda ✅ saya cocokkan dengan README resmi repositori (dibaca 15 Nov 2026). Bagian bertanda ⚠️ berasal dari pengetahuan umum saya tentang paket ini dan **belum saya verifikasi** di README; cocokkan dengan folder `examples/` di repositori bila ada yang tidak berjalan.

---

## 1. Fakta singkat

| Hal | Isi |
|---|---|
| Fungsi | Teks → suara (mp3) + opsional teks berwaktu (subtitle `.srt`/`.vtt`) |
| Kunci API | Tidak perlu |
| Internet | **Perlu** (suara dibuat oleh layanan daring Microsoft) |
| Suara Indonesia | Hanya dua: `id-ID-GadisNeural` (perempuan) dan `id-ID-ArdiNeural` (laki-laki) |
| Yang bisa diatur | Kecepatan (`--rate`), nada (`--pitch`), volume (`--volume`), pilihan suara |
| Yang **tidak** bisa | SSML kustom — ✅ README: dukungan SSML kustom dihapus karena Microsoft memblokir SSML yang tidak bisa dibuat Edge sendiri; kustomisasi terbatas pada opsi pustaka/CLI |
| Lisensi | ⚠️ Repositori memuat berkas `gpl-3.0.txt` (GPL-3.0). Memakainya sebagai alat untuk membuat audio tidak masalah; bila Anda **mendistribusikan kode** yang menyertakannya, baca syarat GPL |
| Status layanan | ⚠️ Memakai layanan suara Edge **secara tidak resmi**. Bisa berubah/terblokir sewaktu-waktu, dan untuk pemakaian komersial statusnya abu-abu. Alternatif aman: rekaman suara sendiri atau layanan berlisensi komersial |

---

## 2. Memasang di laptop (Windows, Python dikelola uv)

Python yang dikelola `uv` menolak pemasangan langsung (`externally-managed-environment`). Jangan pakai `--break-system-packages`. Gunakan **lingkungan terpisah (venv)**.

1. Buat folder kerja, mis. `D:\suara`, lalu buka **Command Prompt (cmd)** di folder itu (klik kotak alamat File Explorer, ketik `cmd`, Enter). Gunakan **cmd**, bukan PowerShell.
2. Buat venv (ganti jalur Python bila berbeda):
   ```
   "D:\Program Files\Python\cpython-3.14.5-windows-x86_64-none\python.exe" -m venv venv
   ```
3. Pasang paketnya di venv ✅:
   ```
   venv\Scripts\python.exe -m pip install edge-tts
   ```
   (README juga menyebut `pipx install edge-tts` bila hanya perlu perintah CLI.)
4. Cek terpasang:
   ```
   venv\Scripts\edge-tts.exe --help
   ```

> Bila memakai folder proyek `promosi` yang sudah punya `venv` dan `requirements.txt` (berisi `edge-tts`), langkah 2–3 tidak perlu: `venv\Scripts\edge-tts.exe` sudah ada.

---

## 3. Perintah dasar (CLI)

Semua contoh dijalankan di cmd pada folder yang berisi `venv`.

### 3.1 Daftar suara ✅
```
venv\Scripts\edge-tts.exe --list-voices
venv\Scripts\edge-tts.exe --list-voices | findstr id-ID
```
Hasil yang diharapkan untuk Indonesia: `id-ID-ArdiNeural` dan `id-ID-GadisNeural`.

### 3.2 Membuat suara dan subtitle ✅
```
venv\Scripts\edge-tts.exe --voice id-ID-GadisNeural --text "Halo, selamat datang di Rumus Langit." --write-media tes.mp3 --write-subtitles tes.srt
```
Menghasilkan `tes.mp3` dan `tes.srt`.

### 3.3 Mengatur kecepatan, nada, volume ✅
```
venv\Scripts\edge-tts.exe --voice id-ID-GadisNeural --rate=-5% --pitch=-2Hz --text "Kamu merasa belum benar-benar kenal dirimu sendiri?" --write-media tes-pelan.mp3
```

**Aturan penting untuk nilai negatif ✅:** tulis dengan tanda sama dengan, tanpa spasi: `--rate=-5%`, `--pitch=-2Hz`. Bentuk `--rate -5%` akan dianggap sebagai opsi lain dan gagal.

| Opsi | Contoh | Arti |
|---|---|---|
| `--rate` | `--rate=+10%`, `--rate=-5%` | Lebih cepat / lebih lambat dari normal |
| `--pitch` | `--pitch=+2Hz`, `--pitch=-2Hz` | Nada lebih tinggi / rendah |
| `--volume` | `--volume=+10%`, `--volume=-10%` | Lebih keras / pelan |
| `--voice` | `--voice id-ID-ArdiNeural` | Memilih suara |
| `--write-media` | `--write-media hasil.mp3` | Menyimpan audio |
| `--write-subtitles` | `--write-subtitles hasil.srt` | Menyimpan subtitle |
| `--no-progress` ✅ | `--no-progress` | Menyembunyikan bilah kemajuan |

### 3.4 Memutar langsung ✅
```
venv\Scripts\edge-playback.exe --voice id-ID-GadisNeural --text "Halo dunia"
```
README: `edge-playback` butuh pemutar `mpv` **kecuali di Windows**, jadi di Windows bisa langsung dipakai. Semua opsi `edge-tts` berlaku, kecuali `--write-media`, `--write-subtitles`, dan `--list-voices`.

### 3.5 Membaca dari berkas teks ⚠️
```
venv\Scripts\edge-tts.exe --voice id-ID-GadisNeural --file naskah.txt --write-media naskah.mp3
```
Simpan `naskah.txt` dengan penyandian **UTF-8**. (Opsi `--file` tidak tercantum di bagian README yang saya baca; cek `edge-tts --help`.)

---

## 4. Penggunaan dari Python ⚠️

Bentuk umum yang dipakai `rakit.py`:

```python
import asyncio
import edge_tts

async def buat():
    c = edge_tts.Communicate("Halo dunia", "id-ID-GadisNeural", rate="+0%", pitch="+0Hz", volume="+0%")
    await c.save("tes.mp3")

asyncio.run(buat())
```
Contoh resmi lain ada di folder `examples/` repositori.

---

## 5. Cara agar suaranya lebih enak didengar

Hanya ada dua suara Indonesia, dan keduanya dilatih untuk membaca teks datar. Yang bisa kita tala:

| Langkah | Alasan |
|---|---|
| Mulai dari `--rate=+0%`, lalu coba `-5%` | Kecepatan terlalu tinggi terdengar kaku |
| Coba `--pitch=-2Hz` | Nada sedikit lebih rendah sering terdengar lebih tenang |
| Pakai kalimat pendek, dengan koma dan titik yang jelas | Tanda baca membuat jeda napas |
| Tulis angka dan singkatan sebagai ucapan (mis. "titik biz titik id") | Mesin kadang salah membaca singkatan |
| Bandingkan Gadis dan Ardi dengan kalimat yang sama | Selera tiap orang berbeda |

Bila setelah itu tetap terdengar kaku, itu batas suaranya. Lanjut ke mesin lain (Gemini TTS atau ElevenLabs, sudah tersedia di `rakit.py --mesin`) atau rekam suara sendiri.

---

## 6. Dipakai bersama perakit video (`rakit.py`)

Perakit memanggil `edge-tts` otomatis per adegan:
```
venv\Scripts\python.exe rakit.py 00 --suara --kecepatan 0
```
- `--suara` memakai Gadis; `--suara ardi` memakai Ardi.
- `--kecepatan` dalam persen (bawaan 20; **0** lebih natural).
- Berkas suara disimpan sebagai `produksi\00\v1.mp3` … `v8.mp3`; bila sudah ada tidak ditimpa. Hapus bila ingin dibuat ulang: `del produksi\00\v*.mp3`.
- Untuk membandingkan satu kalimat saja tanpa merakit video:
  ```
  venv\Scripts\python.exe rakit.py --uji-suara --mesin edge --kecepatan 0
  ```

---

## 7. Mengatasi masalah

| Gejala | Kemungkinan penyebab | Solusi |
|---|---|---|
| `error: externally-managed-environment` saat `pip install` | Python dikelola uv | Pakai venv (§2) |
| `Unexpected token` / perintah tidak jalan, jendela berwarna biru | Memakai PowerShell | Ketik `cmd` untuk pindah, atau buka Command Prompt |
| `503 Invalid response status` | Layanan menolak sementara (sering saat banyak permintaan beruntun) | Ulangi beberapa detik kemudian; beri jeda antar-permintaan (`rakit.py` sudah mencoba ulang) |
| `403` / gagal terhubung terus | ⚠️ Biasanya jam komputer tidak tepat (token layanan bergantung waktu) atau paket sudah usang | Sinkronkan jam Windows, lalu `venv\Scripts\python.exe -m pip install --upgrade edge-tts` |
| `--rate -5%` ditolak | Nilai negatif tanpa `=` | Tulis `--rate=-5%` ✅ |
| Hasil suara tidak ada / berkas kosong | Tanpa internet atau teks kosong | Cek koneksi dan teksnya |
| FFmpeg gagal membaca `v3.mp3` | Berkas setengah jadi dari percobaan yang ditolak | Hapus berkas itu lalu ulangi (`rakit.py` versi terbaru menghapusnya otomatis) |

---

## 8. Ringkasan alur kerja di laptop

1. Siapkan venv dan `pip install edge-tts` (§2).
2. Coba suara dan setelan di §3 dan §5 sampai cocok.
3. Pakai setelan terbaik di perakit: `rakit.py <nomor> --suara [ardi] --kecepatan <angka>`.
4. Bila kurang natural: pindah mesin (`--mesin gemini` atau `--mesin elevenlabs`) atau rekam suara sendiri.

Kunci API untuk mesin lain **jangan** ditulis di berkas atau dikirim ke chat; isi lewat variabel lingkungan di jendela cmd (`set NAMA_KUNCI=...`).

---

*Catatan: dokumen ini diringkas pada 15 Nov 2026 dari README repositori https://github.com/rany2/edge-tts dan pengalaman memakainya di proyek Rumus Langit. Periksa README terbaru untuk perubahan opsi.*
