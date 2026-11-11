<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\BantuanLog;
use App\Models\User;
use App\Services\BantuanBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

/** Endpoint chat bantuan otomatis (pengguna, afiliator, maupun pengunjung). */
class BantuanController extends Controller
{
    public function chat(Request $request, BantuanBotService $bot)
    {
        // Validasi manual: aplikasi hanya merender JSON untuk api/*, jadi $request->validate() akan mengalihkan (302).
        $v = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'pesan'    => 'required|string|min:2|max:500',
            'riwayat'  => 'nullable|array|max:12',
            'riwayat.*.r' => 'nullable|string|max:1',
            'riwayat.*.t' => 'nullable|string|max:600',
        ]);
        if ($v->fails()) {
            return response()->json(['jawaban' => __('bantuan.gagal'), 'sumber' => 'invalid'], 422);
        }
        $data = $v->validated();

        $user = Session::get('user_id') ? User::find(Session::get('user_id')) : null;
        $aff  = Session::get('affiliate_id') ? Affiliate::find(Session::get('affiliate_id')) : null;
        $aff  = ($aff && $aff->isActive()) ? $aff : null;

        // Batas harian per akun / per IP (melindungi kuota AI)
        $kunci = 'bantuan-harian:' . ($user ? "u{$user->id}" : ($aff ? "a{$aff->id}" : 'ip' . $request->ip()));
        if (RateLimiter::tooManyAttempts($kunci, 60)) {
            return response()->json(['jawaban' => __('bantuan.batas_harian'), 'sumber' => 'batas'], 429);
        }
        RateLimiter::hit($kunci, 86400);

        $locale = app()->getLocale();
        [$jawaban, $sumber] = $bot->jawab(trim($data['pesan']), $data['riwayat'] ?? [], $user, $user ? null : $aff, $locale);

        try {
            BantuanLog::create([
                'user_id' => $user?->id, 'affiliate_id' => $user ? null : $aff?->id,
                'pertanyaan' => mb_substr($data['pesan'], 0, 600), 'jawaban' => $jawaban, 'sumber' => $sumber, 'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Log chat bantuan gagal disimpan', ['error' => mb_substr($e->getMessage(), 0, 150)]);
        }

        return response()->json(['jawaban' => $jawaban, 'sumber' => $sumber]);
    }
}
