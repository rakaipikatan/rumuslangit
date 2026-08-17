<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAIReport;
use App\Models\AiReport;
use App\Models\BirthProfile;
use App\Models\User;
use App\Services\KalkulasiEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TrialController extends Controller
{
    public function index()
    {
        if (Session::get('user_id') && Session::get('kalkulasi')) {
            return redirect()->route('trial.hasil');
        }
        return view('trial.index');
    }

    public function mulai()
    {
        if (Session::get('user_id') && Session::get('kalkulasi')) {
            return redirect()->route('trial.hasil');
        }
        return view('trial.mulai');
    }

    public function proses(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:255',
            'jenis_kelamin'      => 'required|in:Laki-laki,Perempuan',
            'dob'                => 'required|date|before:today',
            'birth_hour'         => 'required|integer|min:0|max:23',
            'province'           => 'required|string|max:100',
            'kota'               => 'required|string|max:100',
            'kecamatan'          => 'required|string|max:100',
            'kelurahan'          => 'required|string|max:100',
            'agama'              => 'nullable|string|max:50',
            'agama_lainnya'      => 'nullable|required_if:agama,Others|string|max:100',
            'anak_ke'            => 'required|integer|min:1|max:20',
            'jumlah_saudara'     => 'required|integer|min:1|max:20',
            'status_pernikahan'  => 'required|in:Lajang,Menikah,Cerai Hidup,Cerai Mati',
        ]);

        $kalkulasi = KalkulasiEngine::hitung($request->dob, (int) $request->birth_hour);

        $existingId = Session::get('user_id');
        $user = $existingId ? User::find($existingId) : null;

        $userData = [
            'name'              => $request->name,
            'jenis_kelamin'     => $request->jenis_kelamin,
            'dob'               => $request->dob,
            'birth_hour'        => $request->birth_hour,
            'province'          => $request->province,
            'kota'              => $request->kota,
            'kecamatan'         => $request->kecamatan,
            'kelurahan'         => $request->kelurahan,
            'agama'             => $request->agama,
            'agama_lainnya'     => $request->agama === 'Others' ? $request->agama_lainnya : null,
            'anak_ke'           => $request->anak_ke,
            'jumlah_saudara'    => $request->jumlah_saudara,
            'status_pernikahan' => $request->status_pernikahan,
        ];

        if (!$user) {
            // Kaitkan permanen ke afiliator yang membawa user ini (kalau ada) — hanya
            // saat user pertama kali dibuat, tidak ditimpa saat dia edit data lahirnya.
            $userData['referred_by_affiliate_id'] = Session::get('ref_affiliate_id');
            $user = User::create($userData);
        } else {
            $user->update($userData);
        }

        BirthProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'weton_neptune' => $kalkulasi['weton']['neptu'],
                'shio'          => $kalkulasi['shio']['nama'],
                'zodiac_sign'   => $kalkulasi['zodiak'],
                'element'       => $kalkulasi['elemen'],
                'calculated_at' => now(),
            ]
        );

        Session::put('user_id', $user->id);
        Session::put('kalkulasi', $kalkulasi);

        return redirect()->route('trial.hasil');
    }

    public function hasil()
    {
        $userId    = Session::get('user_id');
        $kalkulasi = Session::get('kalkulasi');

        if (!$userId) {
            return redirect()->route('trial.index');
        }

        $user = User::find($userId);
        if (!$user) {
            Session::forget(['user_id', 'kalkulasi']);
            return redirect()->route('trial.index');
        }

        if (!$kalkulasi) {
            $kalkulasi = KalkulasiEngine::hitung($user->dob->toDateString(), (int) ($user->birth_hour ?? 0));
            Session::put('kalkulasi', $kalkulasi);
        }

        // Cek profil selesai langsung dari DB (tidak bergantung session)
        $locale = app()->getLocale();
        $profilSelesai = AiReport::where('user_id', $user->id)
            ->where('feature_id', 0)
            ->where('locale', $locale)
            ->whereNotNull('response_text')
            ->latest('cached_at')
            ->first();

        $profilData     = null;
        $reportIdProfil = null;

        if ($profilSelesai) {
            // Sudah ada hasil — tampil langsung, jangan dispatch ulang
            $profilData     = json_decode($profilSelesai->response_text, true);
            $reportIdProfil = $profilSelesai->id;
        } else {
            // Belum ada — pastikan job sedang berjalan (dispatch sekali saja)
            $reportIdProfil = $this->ensureProfilJob($user, $kalkulasi, $locale);
        }

        return view('trial.hasil', compact('user', 'kalkulasi', 'profilData', 'reportIdProfil'));
    }

    public function katalog()
    {
        $userId = Session::get('user_id');

        if (!$userId) {
            return redirect()->route('trial.index')
                ->with('info', 'Silakan isi data lahir Anda terlebih dahulu.');
        }

        $user = User::find($userId);

        if (!Session::get('otp_verified') && !($user && $user->email_verified_at)) {
            return redirect()->route('trial.hasil')
                ->with('info', 'Verifikasi email Anda untuk mengakses fitur ini.');
        }

        $fiturAll = \App\Data\FiturData::semua(app()->getLocale());

        return view('trial.katalog', compact('user', 'fiturAll'));
    }

    // AJAX: cek apakah profil AI sudah siap (cari langsung dari DB, bukan session)
    public function profilStatus()
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            return response()->json(['siap' => false]);
        }

        $report = AiReport::where('user_id', $userId)
            ->where('feature_id', 0)
            ->where('locale', app()->getLocale())
            ->whereNotNull('response_text')
            ->latest('cached_at')
            ->first();

        if (!$report) {
            return response()->json(['siap' => false]);
        }

        return response()->json(['siap' => true, 'data' => json_decode($report->response_text, true)]);
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function ensureProfilJob(User $user, array $kalkulasi, string $locale): ?int
    {
        // Jika ada job pending (belum selesai), pakai itu — jangan dispatch baru
        $pending = AiReport::where('user_id', $user->id)
            ->where('feature_id', 0)
            ->where('locale', $locale)
            ->whereNull('response_text')
            ->latest('cached_at')
            ->first();

        if ($pending) {
            return $pending->id;
        }

        // Dispatch job baru
        $userProfile = [
            'name'              => $user->name,
            'dob'               => $user->dob->toDateString(),
            'birth_hour'        => $user->birth_hour ?? 0,
            'province'          => $user->province ?? '-',
            'kecamatan'         => $user->kecamatan ?? '-',
            'kelurahan'         => $user->kelurahan ?? '-',
            'agama'             => $user->agama_display ?? null,
            'anak_ke'           => $user->anak_ke ?? 1,
            'jumlah_saudara'    => $user->jumlah_saudara ?? 1,
            'status_pernikahan' => $user->status_pernikahan ?? '-',
        ];

        $dataHash = hash('sha256', json_encode([0, $user->dob->toDateString(), $user->birth_hour ?? 0, $locale]));

        $report = AiReport::create([
            'user_id'    => $user->id,
            'feature_id' => 0,
            'locale'     => $locale,
            'data_hash'  => $dataHash,
            'cached_at'  => now(),
        ]);

        GenerateAIReport::dispatch($report->id, 0, $kalkulasi, [], $userProfile, [], $locale);

        return $report->id;
    }
}
