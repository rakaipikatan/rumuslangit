<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\KalkulasiEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

    public function logout(Request $request)
    {
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('trial.index');
    }
}
