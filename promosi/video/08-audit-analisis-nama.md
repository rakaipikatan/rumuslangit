# Video 08 — Audit & Analisis Nama

> Prompt produksi video promosi 58 detik (4:5) untuk laporan **Audit & Analisis Nama** (Personal Wellness Hub), ukuran **4:5 / 1080×1350** (standar feed Instagram). Satu video = satu laporan = satu kasus nyata sehari-hari.

## 1. Ringkasan kreatif

| Hal | Isi |
|---|---|
| Durasi | 58 detik (batas aman 56–60) |
| Rasio / resolusi | **4:5 vertikal, 1080×1350 px, 30 fps, MP4 H.264** (standar posting feed Instagram) |
| Laporan yang digambarkan | Audit & Analisis Nama |
| Tokoh | Sinta, 26 tahun, perintis usaha kecil yang sedang memilih nama merek. |
| Kasus | Merasa hidupnya selalu terasa berat dan ragu apakah namanya selaras; juga sedang menyiapkan nama brand baru. |
| Perasaan | ragu dengan nama sendiri dan nama brand → punya pilihan nama dan inisial yang dipahami maknanya |
| Pesan inti | Rumus Langit adalah solusi untuk setiap persimpangan hidup: kamu tidak perlu menebak, cukup isi data lahir dan jawab pertanyaan, lalu dapat peta langkah yang jelas. |
| Bahasa | Voice-over dan teks layar: Bahasa Indonesia |
| CTA | Coba gratis di rumuslangit.biz.id |

### Isi laporan yang ditampilkan (adegan S5–S6)

Skor Resonansi Nama, diagnosis numerologi nama dan elemen lahir, inisial/nama alternatif penyeimbang beserta makna numerologinya, dampak terhadap peluang hidup, forecast 90 hari.

Elemen layar yang direkam/ditiru (data **contoh ilustratif**, bukan hasil nyata siapa pun; beri teks kecil "Contoh tampilan" di pojok bawah):

- Skor Resonansi Nama (contoh: 70 — "Cukup Selaras")
- Inisial penyeimbang dan alternatif nama beserta makna numerologinya
- Penjelasan dampak nama terhadap peluang (%)
- Rekomendasi nama brand
## 2. Kunci konsistensi karakter (WAJIB dilakukan sebelum membuat klip)

Generator video sering mengubah wajah dan pakaian antar-klip. Supaya tokoh tetap orang yang sama dari detik 0 sampai 58, lakukan langkah ini:

**Langkah A — buat lembar karakter (character sheet) sekali saja.** Jalankan prompt di bawah di generator gambar, pilih hasil terbaik, simpan sebagai `08-karakter.png`:

```text
Character reference sheet of SINTA: Indonesian woman, 26 years old, oval face, light-tan skin, shoulder-length dark-brown hair with bangs, small gold hoop earrings, wearing a mustard cardigan over a white t-shirt. Three views on a plain light-grey studio background: full-body front view, 3/4 view, and a close-up head view looking at the camera with a neutral calm expression. Even soft studio lighting, same person in every view, photorealistic, no text, no logo.
```

**Langkah B — kunci di setiap klip.**
1. Pakai gambar lembar karakter sebagai **gambar referensi** (fitur *image-to-video*, *character/subject reference*, atau *ingredients*; nama fitur tergantung generator). Klip pertama boleh *text-to-video*, lalu jadikan **frame terbaiknya** sebagai referensi untuk klip berikutnya.
2. Salin **blok kunci tokoh** di bawah **persis sama, kata demi kata**, di awal prompt tiap adegan yang memperlihatkan tokoh (sudah otomatis ada di prompt adegan S1, S2, S3, S6, S7). Jangan diparafrase.
3. Pakai **seed yang sama** (catat angkanya) dan setelan kamera yang sama: lensa 35 mm, tinggi mata, cahaya hangat keemasan dari samping.
4. **Pakaian tetap sama** sepanjang video (kecuali yang tertulis di blok kunci). Properti utama juga sama (kacamata, hijab, dsb.).
5. Buat **2–3 percobaan per adegan** dan pilih yang wajahnya paling mirip lembar karakter. Bila wajah melenceng, ulangi, jangan dipaksakan.
6. Hindari kerumunan dan banyak wajah dekat kamera; tokoh pendukung cukup dari belakang/siluet/blur agar wajah tokoh utama tidak rusak.
7. Di editor, samakan warna (color grade) semua klip: satu preset yang sama.

**Blok kunci tokoh:**

```text
[CHARACTER SINTA: Indonesian woman, 26 years old, oval face, light-tan skin, shoulder-length dark-brown hair with bangs, small gold hoop earrings, wearing a mustard cardigan over a white t-shirt. Identical face, hair, body and outfit as the reference image in every shot.]
```

## 3. Gaya visual (tempel di awal SETIAP klip)

```text
Vertical 4:5 (1080x1350) cinematic short-form video for the Instagram feed, subject and key action centered inside the safe area (must also survive a 1:1 or 3:4 center crop), 30fps, photorealistic, Indonesian setting and Indonesian characters, dark cosmic night palette (deep navy #060614, violet #7c3aed, warm gold #f59e0b, subtle teal #06b6d4), soft golden rim light and gentle glow, shallow depth of field, slow smooth camera moves, premium calm mood, no on-screen text, no logos, no subtitles, no watermark, no brand names.
```

## 4. Shot list (8 adegan)

Tiap adegan = satu klip. Jika generator hanya mendukung klip 5–8 detik, S2 dan S5 boleh dipecah dua. Susun semuanya di editor (CapCut/Premiere) sesuai waktu di bawah.

### S1 · 0:00–0:05 (5 dtk) — Hook

- **Prompt visual (kirim ke generator, setelah blok Gaya visual):**

  ```text
  [CHARACTER SINTA: Indonesian woman, 26 years old, oval face, light-tan skin, shoulder-length dark-brown hair with bangs, small gold hoop earrings, wearing a mustard cardigan over a white t-shirt. Identical face, hair, body and outfit as the reference image in every shot.] A young woman writing different brand names on sticky notes, crossing them out, a wall full of crumpled notes.
  ```
- **Tokoh:** pakai gambar referensi lembar karakter + seed yang sama.
- **Voice-over:** "Nama itu doa. Tapi apakah namamu benar-benar selaras denganmu?"
- **Teks di layar (ditambah di editor):** Namamu selaras?
- **Musik/SFX:** mulai senyap 0,5 dtk, lalu pad ambient lembut; satu denting lembut di detik 0:04.

### S2 · 0:05–0:14 (9 dtk) — Masalah / kasus

- **Prompt visual (kirim ke generator, setelah blok Gaya visual):**

  ```text
  [CHARACTER SINTA: Indonesian woman, 26 years old, oval face, light-tan skin, shoulder-length dark-brown hair with bangs, small gold hoop earrings, wearing a mustard cardigan over a white t-shirt. Identical face, hair, body and outfit as the reference image in every shot.] She whispers her own name in the mirror, then looks at her business logo draft with doubt; a hand-written name list unfinished.
  ```
- **Tokoh:** pakai gambar referensi lembar karakter + seed yang sama.
- **Voice-over:** "Hidup terasa berat, dan sekarang kamu harus memilih nama untuk usahamu juga."
- **Teks di layar (ditambah di editor):** Nama diri. Nama usaha.
- **Musik/SFX:** ambient berlanjut, volume di bawah voice-over (−18 dB).

### S3 · 0:14–0:20 (6 dtk) — Titik balik: menemukan Rumus Langit

- **Prompt visual (kirim ke generator, setelah blok Gaya visual):**

  ```text
  [CHARACTER SINTA: Indonesian woman, 26 years old, oval face, light-tan skin, shoulder-length dark-brown hair with bangs, small gold hoop earrings, wearing a mustard cardigan over a white t-shirt. Identical face, hair, body and outfit as the reference image in every shot.] She types her full name into the app; letters float like golden dust and rearrange gently.
  ```
- **Tokoh:** pakai gambar referensi lembar karakter + seed yang sama.
- **Voice-over:** "Kamu minta Rumus Langit memeriksa namamu."
- **Teks di layar (ditambah di editor):** Audit namamu
- **Musik/SFX:** musik naik pelan, nada hangat; suara "whoosh" halus saat cahaya emas muncul.

### S4 · 0:20–0:27 (7 dtk) — Cara pakai (isi data)

- **Prompt visual (kirim ke generator, setelah blok Gaya visual):**

  ```text
  Over-the-shoulder close-up of a hand using a smartphone (or a clean screen recording of the real app with a demo account): a date of birth is selected, a birth-time field shows an example, then simple Yes/No answers are tapped one after another; golden highlights on each tap. If the character's hands, sleeve or shoulder are visible, they match: SINTA: a mustard cardigan over a white t-shirt.
  ```
- **Tokoh:** bila tangan/bahu terlihat, pakaian harus sama dengan tokoh utama (tanpa wajah juga tidak apa-apa).
- **Voice-over:** "Isi tanggal lahir dan nama lengkapmu, lalu jawab beberapa pertanyaan Ya atau Tidak."
- **Teks di layar (ditambah di editor):** Isi data lahir • Jawab Ya/Tidak
- **Musik/SFX:** ambient berlanjut, volume di bawah voice-over (−18 dB).

### S5 · 0:27–0:37 (10 dtk) — Laporan terbuka — inti fitur

- **Prompt visual (kirim ke generator, setelah blok Gaya visual):**

  ```text
  Screen recording style: letters of a name glow one by one with numbers beneath; a harmony ring fills; suggested initials appear. If the character's hands, sleeve or shoulder are visible, they match: SINTA: a mustard cardigan over a white t-shirt.
  ```
- **Tokoh:** bila tangan/bahu terlihat, pakaian harus sama dengan tokoh utama (tanpa wajah juga tidak apa-apa).
- **Voice-over:** "Numerologi dan elemen lahirmu dibaca bersama, lalu muncul inisial penyeimbang dan alternatif nama beserta maknanya."
- **Teks di layar (ditambah di editor):** Numerologi • Inisial penyeimbang
- **Musik/SFX:** puncak emosi lembut; denting kecil tiap kartu muncul.

### S6 · 0:37–0:45 (8 dtk) — Langkah nyata dari laporan

- **Prompt visual (kirim ke generator, setelah blok Gaya visual):**

  ```text
  [CHARACTER SINTA: Indonesian woman, 26 years old, oval face, light-tan skin, shoulder-length dark-brown hair with bangs, small gold hoop earrings, wearing a mustard cardigan over a white t-shirt. Identical face, hair, body and outfit as the reference image in every shot.] She picks a name card from the options and holds it with a smile; a brand mock-up (no readable text) appears on a clean table.
  ```
- **Tokoh:** pakai gambar referensi lembar karakter + seed yang sama.
- **Voice-over:** "Kamu pun lebih yakin memilih nama, termasuk nama untuk mereknya."
- **Teks di layar (ditambah di editor):** Pilih nama dengan yakin
- **Musik/SFX:** ambient berlanjut, volume di bawah voice-over (−18 dB).

### S7 · 0:45–0:51 (6 dtk) — Transformasi

- **Prompt visual (kirim ke generator, setelah blok Gaya visual):**

  ```text
  [CHARACTER SINTA: Indonesian woman, 26 years old, oval face, light-tan skin, shoulder-length dark-brown hair with bangs, small gold hoop earrings, wearing a mustard cardigan over a white t-shirt. Identical face, hair, body and outfit as the reference image in every shot.] Opening of her tiny shop with a blank sign, sunlight, warm and proud posture.
  ```
- **Tokoh:** pakai gambar referensi lembar karakter + seed yang sama.
- **Voice-over:** "Dari ragu, menjadi nama yang kamu pilih dengan penuh makna."
- **Teks di layar (ditambah di editor):** Dari ragu → bermakna
- **Musik/SFX:** ambient berlanjut, volume di bawah voice-over (−18 dB).

### S8 · 0:51–0:58 (7 dtk) — Penutup + CTA

- **Prompt visual (kirim ke generator, setelah blok Gaya visual):**

  ```text
  Slow zoom into a calm night sky with a golden compass glow resolving into an EMPTY centered space (logo is added later in the editor). Soft violet and gold gradient background, gentle particles.
  ```
- **Voice-over:** "Rumus Langit. Solusi untuk setiap persimpangan hidup. Coba gratis sekarang di rumus langit titik biz titik id."
- **Teks di layar (ditambah di editor):** Rumus Langit — Solusi untuk setiap persimpangan hidup / Coba gratis: rumuslangit.biz.id / Untuk hiburan & refleksi diri
- **Logo:** tempel `promosi/aset/logo-rumus-langit.png` di tengah (lihat bagian 6). Musik menurun halus, berakhir bersih di 0:58.

## 5. Naskah voice-over utuh (siap disalin)

Suara: narator Indonesia yang hangat, tenang, tempo sedang. Total ±92 kata; dengan jeda napas antar-adegan dan musik, pas untuk 56–58 detik (tempo tenang). Ucapkan alamat situs: "rumus langit titik biz titik id".

```text
Nama itu doa. Tapi apakah namamu benar-benar selaras denganmu?
Hidup terasa berat, dan sekarang kamu harus memilih nama untuk usahamu juga.
Kamu minta Rumus Langit memeriksa namamu.
Isi tanggal lahir dan nama lengkapmu, lalu jawab beberapa pertanyaan Ya atau Tidak.
Numerologi dan elemen lahirmu dibaca bersama, lalu muncul inisial penyeimbang dan alternatif nama beserta maknanya.
Kamu pun lebih yakin memilih nama, termasuk nama untuk mereknya.
Dari ragu, menjadi nama yang kamu pilih dengan penuh makna.
Rumus Langit. Solusi untuk setiap persimpangan hidup. Coba gratis sekarang di rumus langit titik biz titik id.
```

## 6. Logo & end card

- Gunakan **`promosi/aset/logo-rumus-langit.png`** (logo aplikasi dengan tagline berbahasa Inggris sudah dipotong; hanya ikon awan-kompas dan tulisan "RUMUS LANGIT").
- **Jangan** minta generator menggambar logo atau teks (hasilnya huruf rusak). Tempel logo dan semua teks di editor. Kalau generator mendukung gambar-awal/akhir, jadikan S8 sebagai *end frame* kosong lalu timpa logo.
- Area aman: semua teks dan logo di dalam area tengah 1:1 (tidak menempel tepi) agar tidak terpotong di kisi profil Instagram. S8: logo di tengah (lebar ±60% layar) dengan efek muncul 0,5 detik; di bawahnya tagline "Solusi untuk setiap persimpangan hidup", lalu "Coba gratis: rumuslangit.biz.id". Disclaimer kecil di bagian bawah: "Untuk hiburan & refleksi diri".
- Tanda air kecil logo (opasitas 40%) di pojok kanan atas boleh dipakai pada S2–S7.

## 7. Caption & hashtag

```text
Audit & Analisis Nama — Merasa hidupnya selalu terasa berat dan ragu apakah namanya selaras.
Kenali dirimu, temukan arahmu. Coba gratis di rumuslangit.biz.id (link di bio).
#RumusLangit #Weton #Shio #Zodiak #RefleksiDiri #PetaHidup
```

## 8. Negative prompt (isi di kolom negative generator)

```text
text, letters, words, subtitles, captions, watermark, logo, brand names, distorted hands, extra fingers, deformed faces, flicker, jitter, low resolution, blurry, oversaturated, gore, violence, nudity, real celebrity likeness, lottery tickets, gambling, money rain, medical equipment, crystal-ball cliche, horror imagery, skulls, scary ghosts, different person, changing face, face morphing, changing hairstyle, changing outfit between shots, inconsistent character
```

## 9. Aturan wajib & checklist

- [ ] **Jangan menyebut teknologi atau pihak ketiga di balik pembuatan laporan dan pembayaran** (nama mesin, penyedia, atau istilah teknis serupa) di voice-over, teks layar, caption, maupun nama berkas. Bila perlu, sebut laporan "dibuat otomatis oleh sistem Rumus Langit".
- [ ] **Jangan menjanjikan hasil pasti** ("pasti kaya", "pasti jodoh", "dijamin"). Gunakan kata: membantu, peta, panduan, refleksi.
- [ ] Cantumkan "Untuk hiburan & refleksi diri" di end card.
- [ ] Tampilan laporan yang direkam memakai **akun demo dengan nama fiktif**; jangan pernah menampilkan data pengguna asli.
- [ ] Tanpa unsur judi/togel/uang berhamburan; tanpa visual horor/menakutkan; tanpa wajah selebritas.
- [ ] Musik bebas hak cipta (perpustakaan musik editor atau musik royalty-free berlisensi).
- [ ] Durasi akhir 56–60 detik; voice-over jelas di ponsel; teks layar terbaca di area aman (jauh dari tepi atas/bawah).
