<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $endpoint;

    public function __construct()
    {
        $this->apiKey   = config('services.gemini.key');
        $this->model    = config('services.gemini.model', 'gemini-2.5-flash-lite');
        $this->endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    public function generate(int $featureId, array $kalkulasi, array $answers, array $userProfile, array $tarotData = [], string $locale = 'id'): string
    {
        $dataHash = hash('sha256', json_encode(compact('featureId', 'kalkulasi', 'answers', 'tarotData', 'locale')));
        $cacheKey = "gemini:{$locale}:{$featureId}:{$dataHash}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $isProfile = ($featureId === 0);
        $prompt    = $isProfile
            ? $this->buildProfilPrompt($kalkulasi, $userProfile, $locale)
            : $this->buildPrompt($featureId, $kalkulasi, $answers, $userProfile, $tarotData, $locale);

        $sysInstruction = $isProfile
            ? $this->systemInstructionProfil($locale)
            : $this->systemInstruction($locale);

        // Pakai model dari config agar queue worker dan production tetap konsisten.
        $model    = $this->model;
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $payload = [
            'system_instruction' => ['parts' => [['text' => $sysInstruction]]],
            'contents'           => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig'   => [
                'maxOutputTokens'  => $featureId === 12 ? 800 : ($isProfile ? 4096 : 8192),
                'temperature'      => $featureId === 12 ? 0.75 : 0.87,
                'responseMimeType' => 'application/json',
            ],
        ];

        $response  = null;
        $maxTries  = 3;
        $lastError = '';

        for ($attempt = 1; $attempt <= $maxTries; $attempt++) {
            $response = Http::timeout(90)->post("{$endpoint}?key={$this->apiKey}", $payload);

            if ($response->status() === 429) {
                $body         = $response->body();
                $retrySeconds = 60;
                if (preg_match('/retry in ([\d.]+)s/i', $body, $m)) {
                    $retrySeconds = (int) ceil((float) $m[1]);
                }
                throw new \App\Exceptions\GeminiRateLimitException($retrySeconds, 'Gemini 429: ' . substr($body, 0, 200));
            }

            // 503 UNAVAILABLE — transient, retry dengan backoff
            if ($response->status() === 503 && $attempt < $maxTries) {
                sleep($attempt * 5);
                continue;
            }

            if ($response->failed()) {
                $lastError = $response->body();
                if ($attempt < $maxTries) {
                    sleep($attempt * 3);
                    continue;
                }
                throw new \Exception('Gemini API gagal: ' . $lastError);
            }

            break; // sukses
        }

        $text = $response->json('candidates.0.content.parts.0.text', '');

        // Bersihkan markdown code block jika ada (```json ... ```)
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        $text = trim($text);

        // Perbaiki JSON yang tidak valid (misal: newline literal di dalam string value)
        $text = self::repairJson($text);

        Cache::put($cacheKey, $text, now()->addHours(24));

        return $text;
    }

    /**
     * Perbaiki JSON yang mengandung newline literal di dalam string value.
     * Gemini terkadang mengembalikan newline tidak ter-escape di dalam string JSON.
     */
    public static function repairJson(string $text): string
    {
        if (json_decode($text) !== null) {
            return $text; // sudah valid, tidak perlu diperbaiki
        }

        $fixed      = '';
        $inString   = false;
        $escaped    = false;
        $len        = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $c = $text[$i];

            if ($escaped) {
                $escaped = false;
                $fixed  .= $c;
            } elseif ($c === '\\' && $inString) {
                $escaped = true;
                $fixed  .= $c;
            } elseif ($c === '"') {
                $inString = !$inString;
                $fixed   .= $c;
            } elseif ($inString && $c === "\n") {
                $fixed .= '\n'; // escape newline literal
            } elseif ($inString && $c === "\r") {
                // buang \r
            } elseif ($inString && $c === "\t") {
                $fixed .= '\t'; // escape tab literal
            } else {
                $fixed .= $c;
            }
        }

        return $fixed;
    }

    // ─── System Instructions ─────────────────────────────────────────────────

    private function systemInstruction(string $locale): string
    {
        return $locale === 'en'
            ? "You are Rumus Langit, an empathetic, wise, and solution-oriented metaphysical consultant and personal psychologist. Your tone is warm, personal, and flows like a letter from an understanding friend. Avoid clinical or robotic language. Always end with one uplifting, positive affirmation for the user."
            : "Kamu adalah Rumus Langit, seorang konsultan metafisika dan psikolog personal berbahasa Indonesia yang empatik, bijaksana, dan solutif. Gaya bahasamu hangat, personal, dan mengalir seperti surat dari sahabat yang memahami. Hindari bahasa klinis atau robotik. Selalu akhiri dengan satu kalimat afirmasi positif yang membangkitkan semangat pengguna.";
    }

    private function systemInstructionProfil(string $locale): string
    {
        return $locale === 'en'
            ? "You are Rumus Langit, a smart, warm, and honest AI metaphysical consultant. You combine Javanese Primbon, Chinese Shio, and Western Astrology into a highly personal, sharp character reading that feels spot-on. Use casual but substantial English. Emojis are fine in moderation. Don't be clinical. Be like a close friend who really knows the user. IMPORTANT: Output MUST be valid JSON only, with no text outside the JSON."
            : "Kamu adalah Rumus Langit, konsultan metafisika AI yang cerdas, hangat, dan jujur. Kamu menggabungkan ilmu Primbon Jawa, Shio Tionghoa, dan Astrologi Barat menjadi pembacaan karakter yang sangat personal, tajam, dan terasa 'kena'. Gunakan bahasa Indonesia yang santai tapi berbobot. Boleh pakai emoji secukupnya. Jangan klinis. Jadilah seperti sahabat yang sangat mengenal si pengguna. PENTING: Output HARUS berupa JSON valid saja, tanpa teks lain di luar JSON.";
    }

    // ─── Prompt Builders ─────────────────────────────────────────────────────

    private function buildProfilPrompt(array $kalkulasi, array $userProfile, string $locale): string
    {
        return $locale === 'en'
            ? $this->buildProfilPromptEn($kalkulasi, $userProfile)
            : $this->buildProfilPromptId($kalkulasi, $userProfile);
    }

    private function buildProfilPromptId(array $kalkulasi, array $userProfile): string
    {
        $nama       = $userProfile['name'];
        $weton      = $kalkulasi['weton']['weton'];
        $neptu      = $kalkulasi['weton']['neptu'];
        $shio       = $kalkulasi['shio']['nama'];
        $zodiak     = $kalkulasi['zodiak'];
        $elemen     = $kalkulasi['elemen'];
        $dob        = $userProfile['dob'];
        $agama      = $userProfile['agama'] ?? 'spiritualitas pribadi (lintas keyakinan)';
        $anakKe     = $userProfile['anak_ke'] ?? '-';
        $jmlSdr     = $userProfile['jumlah_saudara'] ?? '-';
        $status     = $userProfile['status_pernikahan'] ?? '-';
        $urutan     = "anak ke-{$anakKe} dari {$jmlSdr} bersaudara";

        return <<<PROMPT
Buat profil karakter personal yang SANGAT tajam, spesifik, dan terasa "kena" untuk:

Nama: {$nama}
Lahir: {$dob} | Weton: {$weton} (Neptu {$neptu}) | Shio: {$shio} | Zodiak: {$zodiak} | Elemen: {$elemen}
Agama: {$agama} | Status: {$status} | Urutan lahir: {$urutan}

PETUNJUK PENTING — gunakan semua data ini:
- Urutan lahir ({$urutan}) sangat memengaruhi pola pikir dan peran sosial
- Status {$status} memengaruhi cara memandang hubungan dan prioritas hidup
- Latar {$agama} memengaruhi dimensi spiritual dan nilai-nilai hidup
- Gabungkan ketiganya dengan Weton + Shio + Zodiak untuk insight yang presisi

Kembalikan HANYA JSON valid berikut (semua field wajib diisi, jangan kosong):

{
  "tipe_nama": "2-3 kata archetype unik Bahasa Indonesia yang spesifik untuk kombinasi ini",
  "tipe_tagline": "1 kalimat tajam dan personal — bukan generik",
  "kombinasi": [
    {"sistem": "Weton {$weton} (Primbon)", "karakter": "1 baris karakter spesifik weton ini"},
    {"sistem": "Shio {$shio}", "karakter": "1 baris karakter spesifik shio ini"},
    {"sistem": "{$zodiak}", "karakter": "1 baris karakter spesifik zodiak ini"}
  ],
  "hasil_gabungan": "2-3 kalimat konkret tentang perpaduan unik ketiga sistem + konteks {$urutan} dan status {$status}",
  "karakteristik": ["5-6 butir perilaku nyata yang sangat spesifik untuk profil ini — bukan klise"],
  "catatan_unik": "1-2 kalimat observasi jujur yang 'kena', boleh sedikit humoris, spesifik untuk urutan lahir dan status {$status}",
  "kekuatan": ["6-8 kata/frasa kekuatan konkret"],
  "perlu_dijaga": ["5-6 kata/frasa kelemahan yang realistis"],
  "sisi_spiritual": "2 paragraf tentang dimensi spiritual dari sudut pandang {$agama} yang selaras dengan elemen {$elemen} dan karakter {$weton}",
  "potensi_tersembunyi": "1-2 kalimat potensi unik yang sering tidak disadari orang dengan profil ini",
  "pesan_penutup": "1 kalimat afirmasi hangat dan spesifik — sebutkan nama {$nama}"
}
PROMPT;
    }

    private function buildProfilPromptEn(array $kalkulasi, array $userProfile): string
    {
        $nama       = $userProfile['name'];
        $weton      = $kalkulasi['weton']['weton'];
        $neptu      = $kalkulasi['weton']['neptu'];
        $shio       = $kalkulasi['shio']['nama'];
        $zodiak     = $kalkulasi['zodiak'];
        $elemen     = $kalkulasi['elemen'];
        $dob        = $userProfile['dob'];
        $agama      = $userProfile['agama'] ?? 'personal spirituality (not tied to a specific religion)';
        $anakKe     = $userProfile['anak_ke'] ?? '-';
        $jmlSdr     = $userProfile['jumlah_saudara'] ?? '-';
        $status     = $userProfile['status_pernikahan'] ?? '-';
        $urutan     = "child no. {$anakKe} of {$jmlSdr} siblings";

        return <<<PROMPT
Create a SHARP, specific, "spot-on" personal character profile for:

Name: {$nama}
Born: {$dob} | Weton: {$weton} (Neptu {$neptu}) | Shio: {$shio} | Zodiac: {$zodiak} | Element: {$elemen}
Religion: {$agama} | Status: {$status} | Birth order: {$urutan}

IMPORTANT INSTRUCTIONS — use all of this data:
- Birth order ({$urutan}) strongly influences mindset and social role
- Status {$status} affects how relationships and life priorities are viewed
- {$agama} background shapes the spiritual dimension and life values
- Combine all three with Weton + Shio + Zodiac for precise insight

Note: the Weton/Shio day and pasaran names (e.g. "{$weton}", "{$shio}") are Javanese/Chinese cultural terms — keep them untranslated in your output, exactly as given.

Return ONLY the following valid JSON (every field is required, none may be empty):

{
  "tipe_nama": "2-3 word unique archetype name in English specific to this combination",
  "tipe_tagline": "1 sharp, personal sentence — not generic",
  "kombinasi": [
    {"sistem": "Weton {$weton} (Primbon)", "karakter": "1-line trait specific to this weton"},
    {"sistem": "Shio {$shio}", "karakter": "1-line trait specific to this shio"},
    {"sistem": "{$zodiak}", "karakter": "1-line trait specific to this zodiac"}
  ],
  "hasil_gabungan": "2-3 concrete sentences on the unique blend of all three systems + context of {$urutan} and {$status} status",
  "karakteristik": ["5-6 real, highly specific behavioral traits for this profile — not cliches"],
  "catatan_unik": "1-2 honest, 'spot-on' sentences, slightly humorous if fitting, specific to birth order and {$status} status",
  "kekuatan": ["6-8 concrete strength words/phrases"],
  "perlu_dijaga": ["5-6 realistic weakness words/phrases"],
  "sisi_spiritual": "2 paragraphs on the spiritual dimension from the {$agama} perspective, aligned with the {$elemen} element and {$weton} character",
  "potensi_tersembunyi": "1-2 sentences on a unique potential people with this profile often overlook",
  "pesan_penutup": "1 warm, specific affirmation — mention the name {$nama}"
}
PROMPT;
    }

    private function buildPrompt(int $featureId, array $kalkulasi, array $answers, array $userProfile, array $tarotData, string $locale): string
    {
        // Feature 12 (Tafsir Mimpi) punya prompt khusus yang lebih sederhana
        if ($featureId === 12) {
            return $this->buildMimpiPrompt($kalkulasi, $answers, $userProfile, $locale);
        }

        return $locale === 'en'
            ? $this->buildPromptEn($featureId, $kalkulasi, $answers, $userProfile, $tarotData)
            : $this->buildPromptId($featureId, $kalkulasi, $answers, $userProfile, $tarotData);
    }

    private function buildPromptId(int $featureId, array $kalkulasi, array $answers, array $userProfile, array $tarotData = []): string
    {
        $namaFitur = $this->getNamaFitur($featureId, 'id');
        $agama     = $userProfile['agama'] ?? 'spiritualitas pribadi (lintas keyakinan)';
        $status    = $userProfile['status_pernikahan'] ?? '-';
        $urutan    = isset($userProfile['anak_ke'], $userProfile['jumlah_saudara'])
            ? "anak ke-{$userProfile['anak_ke']} dari {$userProfile['jumlah_saudara']} bersaudara"
            : '-';
        $nama  = $userProfile['name'];
        $weton = $kalkulasi['weton']['weton'];
        $neptu = $kalkulasi['weton']['neptu'];
        $shio  = $kalkulasi['shio']['nama'];
        $zodiak = $kalkulasi['zodiak'];
        $elemen = $kalkulasi['elemen'];

        $golonganDarah = $answers['golongan_darah'] ?? ($userProfile['golongan_darah'] ?? null);

        $pertanyaan  = \App\Data\FiturData::pertanyaan($featureId, 'id');
        $jawabanList = collect($answers)
            ->filter(fn($v, $k) => str_starts_with((string) $k, 'q'))
            ->map(fn($v, $k) => [
                'pertanyaan' => $pertanyaan[((int) filter_var($k, FILTER_SANITIZE_NUMBER_INT)) - 1] ?? $k,
                'jawaban'    => $v ? 'Ya' : 'Tidak',
            ])
            ->values()
            ->toJson(JSON_UNESCAPED_UNICODE);

        $konteksAkhiran = $this->getKonteksFitur($featureId, 'id');

        // Field tambahan khusus feature 11
        $angkaWarnaSchema = '';
        if ($featureId === 11) {
            $angkaWarnaSchema = <<<'SCHEMA'
,
  "angka_hoki": [<angka1 integer 1-99>, <angka2 integer 1-99>, <angka3 integer 1-99>],
  "warna_busana": [
    {"warna": "<nama warna 1>", "hex": "<kode hex warna 1 misal #7c3aed>", "makna": "<kenapa warna ini hoki hari ini untuk profil klien, 1 kalimat>"},
    {"warna": "<nama warna 2>", "hex": "<kode hex warna 2>", "makna": "<alasan warna ini, 1 kalimat>"}
  ]
SCHEMA;
        }

        $tarotSection = '';
        $tarotJsonSchema = '';
        if (!empty($tarotData)) {
            $kartuLines = [];
            foreach ($tarotData as $k) {
                $kartuLines[] = "  - Kartu \"{$k['posisi']}\": {$k['nama']} ({$k['orientasi']}) → {$k['makna']}";
            }
            $kartuText    = implode("\n", $kartuLines);
            $tarotSection = <<<TAROT

BONUS BACAAN 3 KARTU TAROT (kontekskan dengan topik {$namaFitur}):
{$kartuText}
- Integrasikan makna ketiga kartu secara organik ke dalam laporan (bukan terpisah)
- Sertakan field "tarot_reading" di JSON output sesuai skema di bawah
TAROT;

            $tarotJsonSchema = <<<SCHEMA
,
  "tarot_reading": {
    "intro": "<1 kalimat pengantar bacaan tarot dalam konteks {$namaFitur} untuk {$nama}>",
    "kartu": [
      {"posisi": "<nama posisi kartu 1>", "nama": "<nama kartu>", "orientasi": "<Tegak|Terbalik>", "interpretasi": "<2-3 kalimat tafsir kartu ini spesifik untuk situasi {$nama} dan topik {$namaFitur}>"},
      {"posisi": "<nama posisi kartu 2>", "nama": "<nama kartu>", "orientasi": "<Tegak|Terbalik>", "interpretasi": "<2-3 kalimat tafsir>"},
      {"posisi": "<nama posisi kartu 3>", "nama": "<nama kartu>", "orientasi": "<Tegak|Terbalik>", "interpretasi": "<2-3 kalimat tafsir>"}
    ],
    "pesan_tarot": "<1-2 kalimat pesan integratif: apa yang ketiga kartu katakan bersama-sama tentang situasi {$nama}?>"
  }
SCHEMA;
        }

        $golonganDarahLine = ($golonganDarah)
            ? "\nGolongan Darah: {$golonganDarah} — pertimbangkan karakteristik kepribadian & kesehatan sesuai golongan darah ini"
            : '';

        return <<<PROMPT
Kamu adalah konsultan metafisika senior di Rumus Langit. Buat laporan RUMUS Framework yang sangat mendalam, personal, dan actionable untuk:

PROFIL KLIEN:
Nama: {$nama} | Lahir: {$userProfile['dob']} pukul {$userProfile['birth_hour']}.00 WIB
Weton: {$weton} (Neptu {$neptu}) | Shio: {$shio} | Zodiak: {$zodiak} | Elemen: {$elemen}
Agama: {$agama} | Status: {$status} | {$urutan}
Domisili: {$userProfile['kelurahan']}, {$userProfile['kecamatan']}, {$userProfile['province']}{$golonganDarahLine}

TOPIK: {$namaFitur}

JAWABAN KUESIONER (situasi nyata klien saat ini):
{$jawabanList}
{$tarotSection}

INSTRUKSI PENTING:
- Gabungkan data metafisika (fondasi spiritual) + jawaban kuesioner (realita situasi) secara organik
- JANGAN generik. Setiap insight harus terasa "kena" dan spesifik untuk {$nama}
- Framework: Diagnosis → Analisis Sumber → Hambatan → Peta Aksi → Skenario → Rekomendasi → Forecast
- Bahasa Indonesia yang hangat, empatik, dan langsung (bukan klinis/robotik)
- Berikan angka/persentase/skor yang logis dan beragam (bukan semua 80%)
{$konteksAkhiran}

Kembalikan HANYA JSON valid berikut (semua field wajib, isi substansial):
{
  "skor": {
    "nilai": <angka 50-95 berdasarkan kondisi jawaban>,
    "kategori": "<label kondisi spesifik: mis. 'Aliran Tertahan', 'Mengalir Stabil', 'Momentum Kuat'>",
    "level": "<baik|cukup|perlu_perhatian>",
    "deskripsi": "<1 kalimat konteks skor ini>"
  },
  "diagnosis": {
    "kondisi_utama": "<2-3 kalimat diagnosis situasi {$nama} saat ini — spesifik berdasarkan jawaban>",
    "indikator_positif": ["<hal konkret yang sudah berjalan baik>", "<hal positif 2>"],
    "indikator_negatif": ["<hambatan konkret yang terdeteksi>", "<hambatan 2>"],
    "kesimpulan": "<1-2 kalimat inti: akar masalah sesungguhnya bukan gejalanya>"
  },
  "analisis_sumber": {
    "persentase_dominan": <angka 65-90>,
    "jalur_kekuatan": ["<sumber/jalur terkuat 1>", "<jalur 2>", "<jalur 3>"],
    "jalur_lemah": ["<jalur yang kurang efektif 1>", "<jalur lemah 2>"],
    "narasi": "<2 paragraf analisis mendalam yang menggabungkan elemen metafisika + situasi kuesioner>"
  },
  "hambatan": {
    "internal": ["<hambatan dari dalam diri spesifik untuk profil {$nama}>", "<hambatan internal 2>"],
    "eksternal": ["<hambatan lingkungan/situasi berdasarkan jawaban>", "<hambatan eksternal 2>"],
    "siklus_energi": "<1-2 kalimat tentang fase siklus energi saat ini dan kapan akan berubah>"
  },
  "peta_aksi": [
    {
      "ranking": 1,
      "label": "<nama arah/opsi/jalur terbaik>",
      "skor_potensi": <angka 80-95>,
      "deskripsi": "<kenapa ini terbaik untuk {$nama}?>",
      "cocok_untuk": ["<situasi/aktivitas 1>", "<situasi 2>", "<situasi 3>"]
    },
    {
      "ranking": 2,
      "label": "<nama arah/opsi ke-2>",
      "skor_potensi": <angka 65-79>,
      "deskripsi": "<konteks penggunaan>",
      "cocok_untuk": ["<situasi 1>", "<situasi 2>"]
    },
    {
      "ranking": 3,
      "label": "<nama arah/opsi ke-3>",
      "skor_potensi": <angka 55-64>,
      "deskripsi": "<konteks penggunaan>",
      "cocok_untuk": ["<situasi 1>", "<situasi 2>"]
    }
  ],
  "zona_hati_hati": [
    {
      "label": "<hal/arah/situasi yang harus dihindari>",
      "skor_risiko": <angka 30-55>,
      "alasan": ["<risiko konkret 1>", "<risiko 2>"]
    }
  ],
  "skenario": [
    {
      "kondisi": "Jika Tetap Seperti Sekarang",
      "proyeksi_min": <angka %>,
      "proyeksi_max": <angka %>,
      "keterangan": "<penjelasan singkat>"
    },
    {
      "kondisi": "<Jika melakukan perubahan pertama>",
      "proyeksi_min": <lebih tinggi>,
      "proyeksi_max": <lebih tinggi>,
      "keterangan": "<penjelasan>"
    },
    {
      "kondisi": "<Jika menjalankan semua rekomendasi>",
      "proyeksi_min": <tertinggi>,
      "proyeksi_max": <tertinggi>,
      "keterangan": "<penjelasan>"
    }
  ],
  "rekomendasi": [
    {
      "prioritas": 1,
      "aksi": "<tindakan konkret yang bisa dilakukan minggu ini>",
      "impact_bintang": <1-5>,
      "penjelasan": "<kenapa ini penting dan bagaimana caranya>",
      "timing": "<kapan tepatnya melakukan ini>"
    },
    {
      "prioritas": 2,
      "aksi": "<tindakan kedua>",
      "impact_bintang": <1-5>,
      "penjelasan": "<penjelasan>",
      "timing": "<timing>"
    },
    {
      "prioritas": 3,
      "aksi": "<tindakan ketiga>",
      "impact_bintang": <1-5>,
      "penjelasan": "<penjelasan>",
      "timing": "<timing>"
    }
  ],
  "forecast_90_hari": [
    {
      "periode": "Bulan 1",
      "tema": "<tema/fokus bulan ini>",
      "peluang": "<Tinggi|Sedang|Rendah>",
      "risiko_utama": "<risiko spesifik bulan ini>",
      "fokus_aksi": "<apa yang harus dilakukan>"
    },
    {
      "periode": "Bulan 2",
      "tema": "<tema>",
      "peluang": "<level>",
      "risiko_utama": "<risiko>",
      "fokus_aksi": "<aksi>"
    },
    {
      "periode": "Bulan 3",
      "tema": "<tema>",
      "peluang": "<level>",
      "risiko_utama": "<risiko>",
      "fokus_aksi": "<aksi>"
    }
  ],
  "rumus_framework": {
    "root_cause": "<R: Akar masalah sesungguhnya — 1-2 kalimat tajam>",
    "unlock_direction": "<U: Pintu pembuka peluang terbesar — 1-2 kalimat>",
    "momentum_window": "<M: Waktu & kondisi terbaik untuk bergerak — spesifik>",
    "upgrade_action": "<U: Satu tindakan paling berdampak yang bisa dilakukan sekarang>",
    "success_forecast": "<S: Gambaran kondisi jika semua dijalankan — inspiring>"
  },
  "afirmasi": "<1 kalimat afirmasi penutup yang personal untuk {$nama}, hangat dan spesifik>"{$tarotJsonSchema}{$angkaWarnaSchema}
}
PROMPT;
    }

    private function buildPromptEn(int $featureId, array $kalkulasi, array $answers, array $userProfile, array $tarotData = []): string
    {
        $namaFitur = $this->getNamaFitur($featureId, 'en');
        $agama     = $userProfile['agama'] ?? 'personal spirituality (not tied to a specific religion)';
        $status    = $userProfile['status_pernikahan'] ?? '-';
        $urutan    = isset($userProfile['anak_ke'], $userProfile['jumlah_saudara'])
            ? "child no. {$userProfile['anak_ke']} of {$userProfile['jumlah_saudara']} siblings"
            : '-';
        $nama  = $userProfile['name'];
        $weton = $kalkulasi['weton']['weton'];
        $neptu = $kalkulasi['weton']['neptu'];
        $shio  = $kalkulasi['shio']['nama'];
        $zodiak = $kalkulasi['zodiak'];
        $elemen = $kalkulasi['elemen'];

        $golonganDarah = $answers['golongan_darah'] ?? ($userProfile['golongan_darah'] ?? null);

        $pertanyaan  = \App\Data\FiturData::pertanyaan($featureId, 'en');
        $jawabanList = collect($answers)
            ->filter(fn($v, $k) => str_starts_with((string) $k, 'q'))
            ->map(fn($v, $k) => [
                'question' => $pertanyaan[((int) filter_var($k, FILTER_SANITIZE_NUMBER_INT)) - 1] ?? $k,
                'answer'   => $v ? 'Yes' : 'No',
            ])
            ->values()
            ->toJson(JSON_UNESCAPED_UNICODE);

        $konteksAkhiran = $this->getKonteksFitur($featureId, 'en');

        // Field tambahan khusus feature 11
        $angkaWarnaSchema = '';
        if ($featureId === 11) {
            $angkaWarnaSchema = <<<'SCHEMA'
,
  "angka_hoki": [<number1 integer 1-99>, <number2 integer 1-99>, <number3 integer 1-99>],
  "warna_busana": [
    {"warna": "<color name 1>", "hex": "<hex code for color 1, e.g. #7c3aed>", "makna": "<why this color is lucky today for this client's profile, 1 sentence>"},
    {"warna": "<color name 2>", "hex": "<hex code for color 2>", "makna": "<reason for this color, 1 sentence>"}
  ]
SCHEMA;
        }

        $tarotSection = '';
        $tarotJsonSchema = '';
        if (!empty($tarotData)) {
            $kartuLines = [];
            foreach ($tarotData as $k) {
                $kartuLines[] = "  - Card \"{$k['posisi']}\": {$k['nama']} ({$k['orientasi']}) → {$k['makna']}";
            }
            $kartuText    = implode("\n", $kartuLines);
            $tarotSection = <<<TAROT

BONUS 3-CARD TAROT READING (contextualize with the topic {$namaFitur}):
{$kartuText}
- Organically integrate the meaning of all three cards into the report (not as a separate section)
- Include a "tarot_reading" field in the JSON output per the schema below
TAROT;

            $tarotJsonSchema = <<<SCHEMA
,
  "tarot_reading": {
    "intro": "<1 sentence introducing the tarot reading in the context of {$namaFitur} for {$nama}>",
    "kartu": [
      {"posisi": "<name of card position 1>", "nama": "<card name>", "orientasi": "<Upright|Reversed>", "interpretasi": "<2-3 sentence interpretation of this card specific to {$nama}'s situation and the topic {$namaFitur}>"},
      {"posisi": "<name of card position 2>", "nama": "<card name>", "orientasi": "<Upright|Reversed>", "interpretasi": "<2-3 sentence interpretation>"},
      {"posisi": "<name of card position 3>", "nama": "<card name>", "orientasi": "<Upright|Reversed>", "interpretasi": "<2-3 sentence interpretation>"}
    ],
    "pesan_tarot": "<1-2 sentence integrative message: what do all three cards say together about {$nama}'s situation?>"
  }
SCHEMA;
        }

        $golonganDarahLine = ($golonganDarah)
            ? "\nBlood Type: {$golonganDarah} — consider personality & health characteristics associated with this blood type"
            : '';

        return <<<PROMPT
You are a senior metaphysical consultant at Rumus Langit. Create a deeply personal, actionable RUMUS Framework report for:

CLIENT PROFILE:
Name: {$nama} | Born: {$userProfile['dob']} at {$userProfile['birth_hour']}.00 WIB
Weton: {$weton} (Neptu {$neptu}) | Shio: {$shio} | Zodiac: {$zodiak} | Element: {$elemen}
Religion: {$agama} | Status: {$status} | {$urutan}
Location: {$userProfile['kelurahan']}, {$userProfile['kecamatan']}, {$userProfile['province']}{$golonganDarahLine}

TOPIC: {$namaFitur}

QUESTIONNAIRE ANSWERS (client's real current situation):
{$jawabanList}
{$tarotSection}

IMPORTANT INSTRUCTIONS:
- Organically combine metaphysical data (spiritual foundation) + questionnaire answers (real situation)
- DO NOT be generic. Every insight must feel "spot-on" and specific to {$nama}
- Framework: Diagnosis → Source Analysis → Obstacles → Action Map → Scenarios → Recommendations → Forecast
- Warm, empathetic, direct English (not clinical/robotic)
- Provide logical, varied numbers/percentages/scores (not all 80%)
- Note: the Weton/Shio terms (e.g. "{$weton}", "{$shio}") are Javanese/Chinese cultural terms — keep them untranslated in your prose, exactly as given
{$konteksAkhiran}

Return ONLY the following valid JSON (every field required, with substantial content). Keep the JSON field NAMES exactly as shown (do not translate the keys) — only the string VALUES should be in English:
{
  "skor": {
    "nilai": <number 50-95 based on answer conditions>,
    "kategori": "<specific condition label, e.g. 'Blocked Flow', 'Stable Flow', 'Strong Momentum'>",
    "level": "<baik|cukup|perlu_perhatian>",
    "deskripsi": "<1 sentence context for this score>"
  },
  "diagnosis": {
    "kondisi_utama": "<2-3 sentence diagnosis of {$nama}'s current situation — specific, based on answers>",
    "indikator_positif": ["<concrete thing that's already going well>", "<positive point 2>"],
    "indikator_negatif": ["<concrete obstacle detected>", "<obstacle 2>"],
    "kesimpulan": "<1-2 sentence core: the real root cause, not just the symptom>"
  },
  "analisis_sumber": {
    "persentase_dominan": <number 65-90>,
    "jalur_kekuatan": ["<strongest source/path 1>", "<path 2>", "<path 3>"],
    "jalur_lemah": ["<less effective path 1>", "<weak path 2>"],
    "narasi": "<2 paragraphs of deep analysis combining metaphysical elements + questionnaire situation>"
  },
  "hambatan": {
    "internal": ["<internal obstacle specific to {$nama}'s profile>", "<internal obstacle 2>"],
    "eksternal": ["<environmental/situational obstacle based on answers>", "<external obstacle 2>"],
    "siklus_energi": "<1-2 sentences on the current energy cycle phase and when it will shift>"
  },
  "peta_aksi": [
    {
      "ranking": 1,
      "label": "<name of the best direction/option/path>",
      "skor_potensi": <number 80-95>,
      "deskripsi": "<why is this best for {$nama}?>",
      "cocok_untuk": ["<situation/activity 1>", "<situation 2>", "<situation 3>"]
    },
    {
      "ranking": 2,
      "label": "<name of 2nd direction/option>",
      "skor_potensi": <number 65-79>,
      "deskripsi": "<usage context>",
      "cocok_untuk": ["<situation 1>", "<situation 2>"]
    },
    {
      "ranking": 3,
      "label": "<name of 3rd direction/option>",
      "skor_potensi": <number 55-64>,
      "deskripsi": "<usage context>",
      "cocok_untuk": ["<situation 1>", "<situation 2>"]
    }
  ],
  "zona_hati_hati": [
    {
      "label": "<thing/direction/situation to avoid>",
      "skor_risiko": <number 30-55>,
      "alasan": ["<concrete risk 1>", "<risk 2>"]
    }
  ],
  "skenario": [
    {
      "kondisi": "If You Stay As You Are",
      "proyeksi_min": <number %>,
      "proyeksi_max": <number %>,
      "keterangan": "<brief explanation>"
    },
    {
      "kondisi": "<If you make the first change>",
      "proyeksi_min": <higher number>,
      "proyeksi_max": <higher number>,
      "keterangan": "<explanation>"
    },
    {
      "kondisi": "<If you follow all recommendations>",
      "proyeksi_min": <highest number>,
      "proyeksi_max": <highest number>,
      "keterangan": "<explanation>"
    }
  ],
  "rekomendasi": [
    {
      "prioritas": 1,
      "aksi": "<concrete action that can be done this week>",
      "impact_bintang": <1-5>,
      "penjelasan": "<why this matters and how to do it>",
      "timing": "<exactly when to do this>"
    },
    {
      "prioritas": 2,
      "aksi": "<second action>",
      "impact_bintang": <1-5>,
      "penjelasan": "<explanation>",
      "timing": "<timing>"
    },
    {
      "prioritas": 3,
      "aksi": "<third action>",
      "impact_bintang": <1-5>,
      "penjelasan": "<explanation>",
      "timing": "<timing>"
    }
  ],
  "forecast_90_hari": [
    {
      "periode": "Month 1",
      "tema": "<theme/focus this month>",
      "peluang": "<Tinggi|Sedang|Rendah>",
      "risiko_utama": "<specific risk this month>",
      "fokus_aksi": "<what to do>"
    },
    {
      "periode": "Month 2",
      "tema": "<theme>",
      "peluang": "<level>",
      "risiko_utama": "<risk>",
      "fokus_aksi": "<action>"
    },
    {
      "periode": "Month 3",
      "tema": "<theme>",
      "peluang": "<level>",
      "risiko_utama": "<risk>",
      "fokus_aksi": "<action>"
    }
  ],
  "rumus_framework": {
    "root_cause": "<R: The real root cause — 1-2 sharp sentences>",
    "unlock_direction": "<U: The door to the biggest opportunity — 1-2 sentences>",
    "momentum_window": "<M: Best time & conditions to act — specific>",
    "upgrade_action": "<U: The single most impactful action to take now>",
    "success_forecast": "<S: Picture of the outcome if everything is followed through — inspiring>"
  },
  "afirmasi": "<1 personal, warm, specific closing affirmation for {$nama}>"{$tarotJsonSchema}{$angkaWarnaSchema}
}
PROMPT;
    }

    private function buildMimpiPrompt(array $kalkulasi, array $answers, array $userProfile, string $locale): string
    {
        return $locale === 'en'
            ? $this->buildMimpiPromptEn($kalkulasi, $answers, $userProfile)
            : $this->buildMimpiPromptId($kalkulasi, $answers, $userProfile);
    }

    private function buildMimpiPromptId(array $kalkulasi, array $answers, array $userProfile): string
    {
        $nama   = $userProfile['name'];
        $weton  = $kalkulasi['weton']['weton'];
        $neptu  = $kalkulasi['weton']['neptu'];
        $shio   = $kalkulasi['shio']['nama'];
        $zodiak = $kalkulasi['zodiak'];
        $elemen = $kalkulasi['elemen'];
        $agama  = $userProfile['agama'] ?? 'spiritualitas pribadi (lintas keyakinan)';
        $mimpi  = $answers['mimpi'] ?? ($answers['q1'] ?? 'tidak disebutkan');

        return <<<PROMPT
Kamu adalah ahli tafsir mimpi yang memadukan Primbon Jawa, simbolisme Tionghoa, dan psikologi Jungian.

Nama: {$nama} | Weton: {$weton} | Shio: {$shio} | Zodiak: {$zodiak} | Elemen: {$elemen}

Mimpi: "{$mimpi}"

Tulis tafsir personal (2-3 paragraf singkat) yang menghubungkan simbol dalam mimpi dengan profil {$nama}.
Lalu tentukan 5 angka (0-99) yang benar-benar muncul dari simbol dalam mimpi ini.

Kembalikan HANYA JSON:
{
  "interpretasi": "<2-3 paragraf tafsir personal, hangat, tidak menakut-nakuti>",
  "angka_keberuntungan": [<angka1>, <angka2>, <angka3>, <angka4>, <angka5>]
}
PROMPT;
    }

    private function buildMimpiPromptEn(array $kalkulasi, array $answers, array $userProfile): string
    {
        $nama   = $userProfile['name'];
        $weton  = $kalkulasi['weton']['weton'];
        $neptu  = $kalkulasi['weton']['neptu'];
        $shio   = $kalkulasi['shio']['nama'];
        $zodiak = $kalkulasi['zodiak'];
        $elemen = $kalkulasi['elemen'];
        $agama  = $userProfile['agama'] ?? 'personal spirituality (not tied to a specific religion)';
        $mimpi  = $answers['mimpi'] ?? ($answers['q1'] ?? 'not specified');

        return <<<PROMPT
You are a dream interpretation expert who blends Javanese Primbon, Chinese symbolism, and Jungian psychology.

Name: {$nama} | Weton: {$weton} | Shio: {$shio} | Zodiac: {$zodiak} | Element: {$elemen}

Dream: "{$mimpi}"

Write a personal interpretation (2-3 short paragraphs) connecting the symbols in the dream to {$nama}'s profile.
Note: the Weton/Shio terms above are Javanese/Chinese cultural terms — keep them untranslated in your prose.
Then determine 5 numbers (0-99) that genuinely arise from the symbols in this dream.

Return ONLY JSON:
{
  "interpretasi": "<2-3 paragraphs of personal interpretation, warm, not frightening>",
  "angka_keberuntungan": [<number1>, <number2>, <number3>, <number4>, <number5>]
}
PROMPT;
    }

    private function getKonteksFitur(int $featureId, string $locale): string
    {
        if ($locale === 'en') {
            return match($featureId) {
                1  => "- For 'peta_aksi': use compass directions (Northeast, East, North, South, etc.) with match percentages\n- 'skenario' should focus on potential income/wealth increase (%)\n- 'skor' is the Wealth Flow Score",
                2  => "- For 'peta_aksi': use concrete business/investment sectors (Property, Trade, Digital, etc.)\n- 'skenario' should focus on potential return/profit (%)\n- 'skor' is the Financial Profile Score",
                3  => "- For 'peta_aksi': use specific days or time periods (Mon-Wed this week, etc.)\n- 'skenario' should focus on the project's chance of success (%)\n- 'skor' is the Business Momentum Score",
                4  => "- For 'peta_aksi': use concrete layout positions/areas (Desk facing East, Cashier in the North, etc.)\n- 'skenario' should focus on potential increase in customer traffic (%)\n- 'skor' is the Business Location Energy Score",
                5  => "- For 'peta_aksi': use relationship aspects (Communication, Emotional Intimacy, Goal Alignment, etc.)\n- 'skenario' should focus on potential harmony/continuation of the relationship (%)\n- 'skor' is the Compatibility Score",
                6  => "- For 'peta_aksi': use career/office strategies (Building Alliances, Internal Branding, Timing Conversations, etc.)\n- 'skenario' should focus on chance of promotion/improved work conditions (%)\n- 'skor' is the Career Resilience Score",
                7  => "- For 'peta_aksi': use concrete parenting approaches (Experiential Communication, Firm+Warm Boundaries, etc.)\n- 'skenario' should focus on potential improvement in the child's behavior/connection (%)\n- 'skor' is the Parenting Alignment Score",
                8  => "- For 'peta_aksi': use concrete alternative initials/names with their numerological meaning\n- 'skenario' should focus on the name's impact on life opportunities (%)\n- 'skor' is the Name Resonance Score",
                9  => "- For 'peta_aksi': use body zones/health aspects (Digestive System, Sleep Rhythm, etc.)\n- 'skenario' should focus on potential improvement in vitality/energy (%)\n- 'skor' is the Energy Vitality Score\n- Integrate blood type compatibility analysis (if available) with the birth element: discuss physical tendencies, immunity, and aligned dietary patterns",
                10 => "- For 'peta_aksi': use concrete rituals/practices (Morning Meditation, Special Prayer, Specific Fasting, etc.)\n- 'skenario' should focus on potential neutralization of negative energy (%)\n- 'skor' is the Negative Energy Burden Score (higher = needs more attention)",
                11 => "- For 'peta_aksi': use specific number+color+time combinations\n- 'skenario' should focus on opportunities in the coming days\n- 'skor' is the Daily Energy Score",
                12 => "- For 'peta_aksi': use specific dream symbol interpretations and their meaning\n- 'skenario' should focus on the message/warning from the dream\n- 'skor' is the Dream Message Intensity Score",
                default => "",
            };
        }

        return match($featureId) {
            1  => "- Untuk 'peta_aksi': gunakan arah mata angin (Timur Laut, Timur, Utara, Selatan, dll) dengan persentase kecocokan\n- 'skenario' fokus pada potensi peningkatan penghasilan/rezeki (%)\n- 'skor' adalah Skor Aliran Rezeki",
            2  => "- Untuk 'peta_aksi': gunakan sektor bisnis/investasi konkret (Properti, Perdagangan, Digital, dll)\n- 'skenario' fokus pada potensi return/profit (%)\n- 'skor' adalah Skor Profil Finansial",
            3  => "- Untuk 'peta_aksi': gunakan hari-hari spesifik atau periode waktu (Senin-Rabu pekan ini, dll)\n- 'skenario' fokus pada peluang keberhasilan proyek (%)\n- 'skor' adalah Skor Momentum Usaha",
            4  => "- Untuk 'peta_aksi': gunakan posisi/area tata letak konkret (Meja menghadap Timur, Kasir di Utara, dll)\n- 'skenario' fokus pada potensi peningkatan trafik pelanggan (%)\n- 'skor' adalah Skor Energi Tempat Usaha",
            5  => "- Untuk 'peta_aksi': gunakan aspek hubungan (Komunikasi, Keintiman Emosional, Keselarasan Tujuan, dll)\n- 'skenario' fokus pada potensi keharmonisan/kelanjutan hubungan (%)\n- 'skor' adalah Skor Kompatibilitas",
            6  => "- Untuk 'peta_aksi': gunakan strategi karir/kantor (Membangun Aliansi, Branding Internal, Timing Bicara, dll)\n- 'skenario' fokus pada peluang kenaikan posisi/kondisi kerja (%)\n- 'skor' adalah Skor Ketahanan Karir",
            7  => "- Untuk 'peta_aksi': gunakan pendekatan parenting konkret (Komunikasi Experiential, Batas Tegas+Hangat, dll)\n- 'skenario' fokus pada potensi perbaikan perilaku/koneksi anak (%)\n- 'skor' adalah Skor Keselarasan Pola Asuh",
            8  => "- Untuk 'peta_aksi': gunakan inisial/nama alternatif yang konkret dengan makna numerologinya\n- 'skenario' fokus pada dampak nama terhadap peluang hidup (%)\n- 'skor' adalah Skor Resonansi Nama",
            9  => "- Untuk 'peta_aksi': gunakan zona tubuh/aspek kesehatan (Sistem Pencernaan, Ritme Tidur, dll)\n- 'skenario' fokus pada potensi perbaikan vitalitas/energi (%)\n- 'skor' adalah Skor Vitalitas Energi\n- Integrasikan analisis kecocokan golongan darah (jika tersedia) dengan elemen lahir: bahas kecenderungan fisik, imunitas, dan pola makan yang selaras",
            10 => "- Untuk 'peta_aksi': gunakan ritual/praktik konkret (Meditasi Pagi, Doa Khusus, Puasa Tertentu, dll)\n- 'skenario' fokus pada potensi netralisasi energi negatif (%)\n- 'skor' adalah Skor Beban Energi Negatif (makin tinggi = makin perlu perhatian)",
            11 => "- Untuk 'peta_aksi': gunakan kombinasi angka+warna+waktu yang spesifik\n- 'skenario' fokus pada peluang hari-hari ke depan\n- 'skor' adalah Skor Energi Harian",
            12 => "- Untuk 'peta_aksi': gunakan interpretasi simbol mimpi yang spesifik dan maknanya\n- 'skenario' fokus pada pesan/peringatan dari mimpi tersebut\n- 'skor' adalah Skor Intensitas Pesan Mimpi",
            default => "",
        };
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function getNamaFitur(int $id, string $locale): string
    {
        if ($locale === 'en') {
            return match($id) {
                0  => 'Free Character Profile',
                1  => 'Wealth Compass Direction',
                2  => 'Business Potential & Investment Style',
                3  => 'Auspicious Days & Business Momentum',
                4  => 'Business Location & Storefront Direction',
                5  => 'Love Compatibility Calculator',
                6  => 'Workplace Toxicity & Office Politics Radar',
                7  => 'Child\'s Character & Parenting Style',
                8  => 'Name Audit & Analysis',
                9  => 'Health & Energy Vitality',
                10 => 'Cleansing Ritual & Bad Luck Mitigation Guide',
                11 => 'Daily Lucky Numbers & Colors',
                12 => 'Dream Interpretation',
                default => 'Personal Analysis',
            };
        }

        return match($id) {
            0  => 'Profil Karakter Gratis',
            1  => 'Arah Mata Angin Rezeki',
            2  => 'Potensi Bisnis & Gaya Investasi',
            3  => 'Hari Baik & Momentum Usaha',
            4  => 'Arah Tempat Usaha & Ruko',
            5  => 'Kalkulator Jodoh & Asmara',
            6  => 'Radar Toksisitas & Politik Kantor',
            7  => 'Karakter Anak & Pola Asuh',
            8  => 'Audit & Analisis Nama',
            9  => 'Kesehatan & Vitalitas Energi',
            10 => 'Panduan Ruwat & Mitigasi Sial',
            11 => 'Angka & Warna Keberuntungan Harian',
            12 => 'Tafsir Mimpi',
            default => 'Analisis Personal',
        };
    }
}
