<?php

namespace App\Http\Controllers;

use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class OtpController extends Controller
{
    public function __construct(private EmailOtpService $emailOtp) {}

    public function kirim(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Masukkan alamat email yang valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim($request->email));
        $hash  = hash('sha256', $email);

        $isLocal = app()->environment('local');

        if (!$isLocal && $this->emailOtp->sudahPakaiTrial($hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ini telah mencapai batas maksimum penggunaan trial harian.',
            ], 429);
        }

        $attempts = Session::get("otp_attempts:{$hash}", 0);
        if (!$isLocal && $attempts >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan. Coba lagi dalam 5 menit.',
            ], 429);
        }

        try {
            $this->emailOtp->kirimOTP($email);
            Session::put('otp_email_hash', $hash);
            Session::put('otp_email_value', $email);
            Session::increment("otp_attempts:{$hash}");

            return response()->json([
                'success' => true,
                'message' => 'Kode OTP telah dikirim ke email Anda.',
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Email OTP send failed', [
                'email_hash' => substr($hash, 0, 8) . '…',
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP. Pastikan alamat email valid lalu coba lagi.',
            ], 500);
        }
    }

    public function verifikasi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Masukkan 6 digit kode OTP.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $hash  = Session::get('otp_email_hash');
        $email = Session::get('otp_email_value');

        if (!$hash) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi OTP tidak ditemukan. Minta kode baru.',
            ], 400);
        }

        $berhasil = $this->emailOtp->verifikasiOTP($hash, $request->kode_otp);

        if (!$berhasil) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP salah atau sudah kedaluwarsa.',
            ], 422);
        }

        $userId = Session::get('user_id');
        if ($userId && $email) {
            $existingUser = \App\Models\User::where('email', $email)
                ->where('id', '!=', $userId)
                ->whereNotNull('email_verified_at')
                ->first();

            if ($existingUser) {
                \App\Models\User::where('id', $userId)
                    ->whereNull('email')
                    ->whereNull('email_verified_at')
                    ->delete();
                Session::put('user_id', $existingUser->id);
            } else {
                \App\Models\User::where('id', $userId)->update([
                    'email'             => $email,
                    'email_verified_at' => now(),
                ]);
            }
        }

        Session::put('otp_verified', true);
        Session::forget('otp_email_hash');
        Session::forget('otp_email_value');

        return response()->json([
            'success'  => true,
            'message'  => 'Verifikasi berhasil!',
            'redirect' => route('trial.katalog'),
        ]);
    }
}
