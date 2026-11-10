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

    // GET /pengaturan — pengaturan akun (saat ini: atur password).
    public function pengaturan()
    {
        $user = $this->penggunaTerverifikasi();
        if (!$user) {
            return redirect()->route('auth.login.form');
        }

        return view('auth.pengaturan', compact('user'));
    }

    // POST /pengaturan/password — buat password (akun belum punya) atau ubah (wajib password lama).
    public function passwordSimpan(Request $request)
    {
        $user = $this->penggunaTerverifikasi();
        if (!$user) {
            return redirect()->route('auth.login.form');
        }

        $punyaPassword = filled($user->password);

        $request->validate([
            'password'         => 'required|string|min:8|max:100|confirmed',
            'current_password' => $punyaPassword ? 'required|string' : 'nullable',
        ]);

        if ($punyaPassword && !Hash::check((string) $request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => __('auth.password_lama_salah')]);
        }

        $user->update(['password' => $request->password]); // cast 'hashed' pada model

        return redirect()->route('auth.pengaturan')->with('success', __('auth.password_tersimpan'));
    }

    // Pengguna yang sedang login dengan email terverifikasi (syarat mengatur password).
    private function penggunaTerverifikasi(): ?User
    {
        $id = Session::get('user_id');
        $user = $id ? User::find($id) : null;

        return ($user && $user->email && $user->email_verified_at) ? $user : null;
    }

    public function logout(Request $request)
    {
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('trial.index');
    }
}
