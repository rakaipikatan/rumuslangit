<?php

namespace App\Http\Controllers;

use App\Data\FiturData;
use App\Jobs\GenerateAIReport;
use App\Models\AiReport;
use App\Models\QuestionnaireSession;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\KalkulasiEngine;
use App\Support\HariWib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ReportController extends Controller
{
    /**
     * Tampilkan form kuesioner + cek akses.
     */
    public function form(int $featureId)
    {
        $fitur = FiturData::cari($featureId);
        if (!$fitur) abort(404);

        $user = $this->userDariSession();
        if (!$user) {
            return redirect()->route('trial.index')
                ->with('info', 'Silakan isi data lahir Anda terlebih dahulu.');
        }

        if (!Session::get('otp_verified') && !$user->email_verified_at) {
            return redirect()->route('trial.hasil')
                ->with('info', 'Verifikasi email Anda untuk mengakses laporan ini.');
        }

        // Cek akses: freemium, pelanggan aktif, atau punya jatah dari pembelian satuan
        if (!$user->hasAccessToFeature($featureId)) {
            $jatahHabis    = $user->pernahBeli($featureId); // sudah beli tapi jatahnya terpakai semua
            $laporanTerakhir = $this->laporanTerakhir($user, $featureId);
            return view('laporan.paywall', compact('user', 'fitur', 'featureId', 'jatahHabis', 'laporanTerakhir'));
        }

        // Info jatah (hanya relevan untuk pembeli satuan yang bukan pelanggan) + tautan laporan terakhir
        $kuota = (!$user->isSubscriber() && empty($fitur['freemium']))
            ? ['total' => $user->kuotaTotal($featureId), 'sisa' => $user->kuotaSisa($featureId)]
            : null;
        $laporanTerakhir = $this->laporanTerakhir($user, $featureId);

        // Fitur 12: Tafsir Mimpi pakai form khusus (input teks bebas)
        if ($featureId === 12) {
            return view('laporan.form_mimpi', compact('user', 'fitur', 'featureId', 'laporanTerakhir'));
        }

        return view('laporan.form', compact('user', 'fitur', 'featureId', 'kuota', 'laporanTerakhir'));
    }

    /**
     * Proses jawaban kuesioner → dispatch job AI.
     */
    public function proses(int $featureId, Request $request)
    {
        $fitur = FiturData::cari($featureId);
        if (!$fitur) abort(404);

        $user = $this->userDariSession();
        if (!$user) abort(403, 'Sesi tidak ditemukan.');

        if (!$user->hasAccessToFeature($featureId)) abort(403, 'Akses ditolak.');

        // Fitur 12: satu input teks bebas
        if ($featureId === 12) {
            $validated = $request->validate([
                'mimpi' => 'required|string|min:5|max:500',
            ]);
        } else {
            $rules = [];
            foreach (range(1, count($fitur['pertanyaan'])) as $i) {
                $rules["q{$i}"] = 'required|boolean';
            }
            if (!empty($fitur['tanya_golongan_darah'])) {
                $rules['golongan_darah'] = 'required|in:A,B,AB,O';
            }
            // Fitur 5 (Jodoh): wajib isi data pasangan
            if ($featureId === 5) {
                $rules['p_nama']             = 'required|string|max:255';
                $rules['p_dob']              = 'required|date|before:today';
                $rules['p_birth_hour']       = 'nullable|integer|min:0|max:23';
                $rules['p_jenis_kelamin']    = 'nullable|in:Laki-laki,Perempuan';
                $rules['p_province']         = 'required|string|max:100';
                $rules['p_kota']             = 'required|string|max:100';
                $rules['p_kecamatan']        = 'required|string|max:100';
                $rules['p_kelurahan']        = 'required|string|max:100';
                $rules['p_agama']            = 'required|string|max:50';
                $rules['p_status_pernikahan']= 'required|in:Lajang,Menikah,Cerai Hidup,Cerai Mati';
                $rules['p_anak_ke']          = 'required|integer|min:1|max:20';
                $rules['p_jumlah_saudara']   = 'required|integer|min:1|max:20';
                $rules['p_pekerjaan']        = 'required|string|max:100';
            }
            $validated = $request->validate($rules);
        }

        // Ambil data tarot opsional
        $tarotData = [];
        if ($request->has('tarot') && is_array($request->input('tarot'))) {
            foreach ($request->input('tarot') as $kartu) {
                if (!empty($kartu['nama']) && !empty($kartu['posisi']) && !empty($kartu['orientasi'])) {
                    $tarotData[] = [
                        'posisi'    => strip_tags($kartu['posisi']),
                        'nama'      => strip_tags($kartu['nama']),
                        'orientasi' => in_array($kartu['orientasi'], ['tegak', 'terbalik']) ? $kartu['orientasi'] : 'tegak',
                        'makna'     => strip_tags($kartu['makna'] ?? ''),
                    ];
                }
            }
            $tarotData = array_slice($tarotData, 0, 3);
        }

        // Simpan sesi kuesioner
        QuestionnaireSession::create([
            'user_id'    => $user->id,
            'feature_id' => $featureId,
            'answers'    => $validated,
        ]);

        // Buat record laporan (kosong dulu, diisi job)
        $locale    = app()->getLocale();
        $kalkulasi = $this->kalkulasiUser($user);
        $dataHash  = hash('sha256', json_encode([$featureId, $kalkulasi, $validated, $tarotData, $locale]));

        // Jawaban yang sama di HARI YANG SAMA (WIB) memakai laporan tersimpan — hasil identik, tanpa AI, tanpa jatah.
        // Hari berikutnya, jawaban yang sama membuat laporan BARU (tetap tanpa memotong jatah).
        // (Bila fitur dikeluarkan dari config payment.fitur_harian, laporan tersimpan dipakai selamanya.)
        $existing = AiReport::where('user_id', $user->id)
            ->where('feature_id', $featureId)
            ->where('locale', $locale)
            ->where('data_hash', $dataHash)
            ->when(!empty($fitur['harian']), fn ($q) => $q->where('cached_at', '>=', HariWib::awal()))
            ->whereNotNull('response_text')
            ->latest('id')
            ->first();

        if ($existing) {
            Session::put("report_id_{$featureId}", $existing->id);
            return redirect()->route('laporan.hasil', $featureId);
        }

        // Batas wajar harian (melindungi biaya AI) — hanya untuk pembuatan BARU, bukan laporan identik dari cache.
        if ($pesan = $this->batasHarianTercapai($user, $featureId, $fitur)) {
            return back()->withErrors(['batas' => $pesan])->withInput();
        }

        $report = AiReport::create([
            'user_id'        => $user->id,
            'feature_id'     => $featureId,
            'locale'         => $locale,
            // Pembeli satuan memakai 1 jatah hanya untuk kumpulan jawaban yang BERBEDA dari yang pernah dibuat
            // (jawaban sama di hari lain / bahasa lain tetap membuat laporan baru, tanpa memakai jatah).
            'consumed_quota' => !$user->isSubscriber() && empty($fitur['freemium'])
                && $this->pakaiJatahUntuk($user, $featureId, $kalkulasi, $validated, $tarotData),
            'data_hash'      => $dataHash,
            'cached_at'      => now(),
        ]);

        Session::put("report_id_{$featureId}", $report->id);

        $userProfile = [
            'name'              => $user->name,
            'jenis_kelamin'     => $user->jenis_kelamin ?? '-',
            'dob'               => $user->dob->toDateString(),
            'birth_hour'        => $user->birth_hour,
            'province'          => $user->province,
            'kota'              => $user->kota ?? '-',
            'kecamatan'         => $user->kecamatan,
            'kelurahan'         => $user->kelurahan,
            'agama'             => $user->agama_display ?? null,
            'anak_ke'           => $user->anak_ke,
            'jumlah_saudara'    => $user->jumlah_saudara,
            'status_pernikahan' => $user->status_pernikahan ?? '-',
        ];

        if (!empty($validated['golongan_darah'])) {
            $userProfile['golongan_darah'] = $validated['golongan_darah'];
        }

        // Fitur 5: sertakan data pasangan ke profil user untuk AI prompt
        if ($featureId === 5 && !empty($validated['p_nama'])) {
            $userProfile['pasangan'] = [
                'nama'             => $validated['p_nama'],
                'jenis_kelamin'    => $validated['p_jenis_kelamin'] ?? ($user->jenis_kelamin === 'Laki-laki' ? 'Perempuan' : 'Laki-laki'),
                'dob'              => $validated['p_dob'],
                'birth_hour'       => $validated['p_birth_hour'] ?? 0,
                'province'         => $validated['p_province'],
                'kota'             => $validated['p_kota'],
                'kecamatan'        => $validated['p_kecamatan'],
                'kelurahan'        => $validated['p_kelurahan'],
                'agama'            => $validated['p_agama'],
                'status_pernikahan'=> $validated['p_status_pernikahan'],
                'anak_ke'          => $validated['p_anak_ke'],
                'jumlah_saudara'   => $validated['p_jumlah_saudara'],
                'pekerjaan'        => $validated['p_pekerjaan'],
            ];
        }

        // Feature 12 (Tafsir Mimpi): coba langsung, fallback ke queue jika gagal
        if ($featureId === 12) {
            try {
                $ai   = app(GeminiService::class);
                $text = $ai->generate($featureId, $kalkulasi, $validated, $userProfile, [], $locale);
                $report->update(['response_text' => $text, 'provider' => $ai->penyedia]);
                return redirect()->route('laporan.hasil', $featureId);
            } catch (\Throwable) {
                // Gagal (misal 503) — proses via queue seperti fitur lain
                GenerateAIReport::dispatch($report->id, $featureId, $kalkulasi, $validated, $userProfile, [], $locale);
                return redirect()->route('laporan.status', $featureId);
            }
        }

        GenerateAIReport::dispatch($report->id, $featureId, $kalkulasi, $validated, $userProfile, $tarotData, $locale);

        return redirect()->route('laporan.status', $featureId);
    }

    /**
     * Halaman tunggu — polling status via AJAX.
     */
    public function status(int $featureId, Request $request)
    {
        $fitur    = FiturData::cari($featureId);
        $reportId = Session::get("report_id_{$featureId}");

        if (!$reportId) {
            return redirect()->route('laporan.form', $featureId);
        }

        // AJAX polling
        if ($request->expectsJson()) {
            $siap = AiReport::where('id', $reportId)
                ->whereNotNull('response_text')
                ->exists();

            return response()->json([
                'siap'     => $siap,
                'redirect' => $siap ? route('laporan.hasil', $featureId) : null,
            ]);
        }

        return view('laporan.status', compact('fitur', 'featureId', 'reportId'));
    }

    /**
     * Tampilkan hasil laporan AI.
     */
    public function hasil(int $featureId)
    {
        $fitur    = FiturData::cari($featureId);
        $reportId = Session::get("report_id_{$featureId}");

        if (!$reportId) {
            return redirect()->route('laporan.form', $featureId);
        }

        $report = AiReport::find($reportId);

        if (!$report || !$report->response_text) {
            return redirect()->route('laporan.status', $featureId);
        }

        $user      = $this->userDariSession();
        $kalkulasi = $this->kalkulasiUser($user);

        return view('laporan.hasil', compact('user', 'fitur', 'featureId', 'report', 'kalkulasi'));
    }

    /** Pesan error bila batas harian tercapai; null bila masih boleh. */
    private function batasHarianTercapai(User $user, int $featureId, array $fitur): ?string
    {
        $awalHari = HariWib::awal();

        if ($user->isSubscriber()) {
            $batas = (int) config('payment.subscriber_daily_reports', 20);
            $hariIni = AiReport::where('user_id', $user->id)->where('feature_id', '>', 0)->where('cached_at', '>=', $awalHari)->count();

            return $hariIni >= $batas ? __('laporan.batas.langganan', ['n' => $batas]) : null;
        }

        if (!empty($fitur['freemium'])) {
            $batas = (int) config('payment.free_daily_reports', 5);
            $hariIni = AiReport::where('user_id', $user->id)->where('feature_id', $featureId)->where('cached_at', '>=', $awalHari)->count();

            return $hariIni >= $batas ? __('laporan.batas.gratis', ['n' => $batas]) : null;
        }

        return null;
    }

    /**
     * Haruskah pembuatan baru ini memakai 1 jatah? Hanya bila kumpulan jawaban ini BELUM pernah dibuat
     * (apa pun bahasa/harinya). "Pernah dibuat" = sudah selesai, atau sedang diproses (15 menit terakhir).
     *
     * Percobaan lama yang gagal/macet tidak lagi menanggung jatah: bebannya dipindah ke pembuatan baru ini,
     * sehingga satu kumpulan jawaban tidak pernah memotong jatah dua kali walau percobaan lama selesai belakangan.
     */
    private function pakaiJatahUntuk(User $user, int $featureId, array $kalkulasi, array $validated, array $tarotData): bool
    {
        $hashSemuaBahasa = collect(['id', 'en'])
            ->map(fn ($loc) => hash('sha256', json_encode([$featureId, $kalkulasi, $validated, $tarotData, $loc])))
            ->all();

        $serupa = AiReport::where('user_id', $user->id)
            ->where('feature_id', $featureId)
            ->whereIn('data_hash', $hashSemuaBahasa)
            ->get();

        $sudahAda = $serupa->contains(fn ($r) => $r->response_text !== null
            || ($r->cached_at && $r->cached_at->gte(now()->subMinutes(15))));

        if ($sudahAda) {
            return false;
        }

        if ($serupa->isNotEmpty()) {
            AiReport::whereIn('id', $serupa->pluck('id'))->update(['consumed_quota' => false]);
        }

        return true;
    }

    private function laporanTerakhir(User $user, int $featureId): ?AiReport
    {
        return AiReport::where('user_id', $user->id)
            ->where('feature_id', $featureId)
            ->whereNotNull('response_text')
            ->latest('id')
            ->first();
    }

    /**
     * Buka kembali laporan terakhir yang pernah dibuat (hasil tersimpan selamanya), tanpa memakai jatah.
     * Tidak butuh akses aktif: laporan sudah dibayar/dibuat.
     */
    public function terakhir(int $featureId)
    {
        $user = $this->userDariSession();
        if (!$user) {
            return redirect()->route('trial.index');
        }

        $report = $this->laporanTerakhir($user, $featureId);
        if (!$report) {
            return redirect()->route('laporan.form', $featureId);
        }

        Session::put("report_id_{$featureId}", $report->id);

        return redirect()->route('laporan.hasil', $featureId);
    }

    private function userDariSession(): ?User
    {
        $userId = Session::get('user_id');
        return $userId ? User::find($userId) : null;
    }

    private function kalkulasiUser(?User $user): array
    {
        if (!$user) return [];

        $fromSession = Session::get('kalkulasi');
        if ($fromSession) return $fromSession;

        $kalkulasi = KalkulasiEngine::hitung(
            $user->dob->toDateString(),
            (int) $user->birth_hour
        );
        Session::put('kalkulasi', $kalkulasi);
        return $kalkulasi;
    }
}
