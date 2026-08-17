<?php

namespace App\Http\Controllers;

use App\Data\FiturData;
use App\Jobs\GenerateAIReport;
use App\Models\AiReport;
use App\Models\QuestionnaireSession;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\KalkulasiEngine;
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

        // Cek akses: subscriber atau sudah bayar fitur ini
        if (!$user->hasAccessToFeature($featureId)) {
            return view('laporan.paywall', compact('user', 'fitur', 'featureId'));
        }

        // Fitur 12: Tafsir Mimpi pakai form khusus (input teks bebas)
        if ($featureId === 12) {
            return view('laporan.form_mimpi', compact('user', 'fitur', 'featureId'));
        }

        return view('laporan.form', compact('user', 'fitur', 'featureId'));
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

        // Cek cache: laporan identik sudah pernah dibuat (untuk locale yang sama)
        $existing = AiReport::where('user_id', $user->id)
            ->where('feature_id', $featureId)
            ->where('locale', $locale)
            ->where('data_hash', $dataHash)
            ->whereNotNull('response_text')
            ->first();

        if ($existing) {
            Session::put("report_id_{$featureId}", $existing->id);
            return redirect()->route('laporan.hasil', $featureId);
        }

        $report = AiReport::create([
            'user_id'    => $user->id,
            'feature_id' => $featureId,
            'locale'     => $locale,
            'data_hash'  => $dataHash,
            'cached_at'  => now(),
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
                $text = app(GeminiService::class)->generate($featureId, $kalkulasi, $validated, $userProfile, [], $locale);
                $report->update(['response_text' => $text]);
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
