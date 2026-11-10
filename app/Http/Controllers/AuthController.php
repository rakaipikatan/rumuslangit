<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailOtpService;
use App\Services\KalkulasiEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function loginForm()
    {
        if (Session::get('user_id')) {
            return redirect()->route('trial.hasil');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password tidak sesuai.']);
        }

        $kalkulasi = KalkulasiEngine::hitung(
            $user->dob->toDateString(),
            (int) $user->birth_hour
        );

        Session::regenerate();
        Session::put('user_id', $user->id);
        Session::put('kalkulasi', $kalkulasi);
        Session::put('otp_verified', true);

        return redirect()->intended(route('trial.hasil'));
    }

    // POST /login/kode — kirim kode login ke email. Respons selalu sama (tidak membocorkan email terdaftar atau tidak).
    public function kodeKirim(Request $request, EmailOtpService $otp)
    {
        $request->validate(['email' => 'required|email|max:255']);

        $email = strtolower(trim($request->email));
        $hash  = hash('sha256', $email);

        // Batas per email: 3 kode / 10 menit (selain throttle per IP di route)
        RateLimiter::attempt("login-kode:{$hash}", 3, function () use ($email, $otp) {
            $ada = User::whereRaw('lower(email) = ?', [$email])->whereNotNull('email_verified_at')->exists();
            if ($ada) {
                try {
                    $otp->kirimOTP($email);
                } catch (\Throwable $e) {
                    \Log::warning('Kode login gagal dikirim', ['error' => $e->getMessage()]);
                }
            }
        }, 600);

        Session::put('login_kode_hash', $hash);
        Session::put('login_kode_email', $email);

        return response()->json(['success' => true, 'message' => __('auth.kode_terkirim')]);
    }

    // POST /login/kode/verifikasi — tukar kode dengan sesi login.
    public function kodeVerifikasi(Request $request, EmailOtpService $otp)
    {
        $request->validate(['kode_otp' => 'required|string|size:6']);

        $hash  = Session::get('login_kode_hash');
        $email = Session::get('login_kode_email');

        if (!$hash || !$email || !$otp->periksaKode($hash, $request->kode_otp)) {
            return response()->json(['success' => false, 'message' => __('auth.kode_salah')], 422);
        }

        $user = User::whereRaw('lower(email) = ?', [$email])->whereNotNull('email_verified_at')->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('auth.kode_salah')], 422);
        }

        Session::forget(['login_kode_hash', 'login_kode_email']);
        $request->session()->regenerate();
        Session::put('user_id', $user->id);
        Session::put('kalkulasi', KalkulasiEngine::hitung($user->dob->toDateString(), (int) $user->birth_hour));
        Session::put('otp_verified', true);

        return response()->json(['success' => true, 'redirect' => route('trial.katalog')]);
    }

    public function logout(Request $request)
    {
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('trial.index');
    }
}
