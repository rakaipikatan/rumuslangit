<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\EmailVerification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailOtpService
{
    public function kirimOTP(string $email): void
    {
        $otp  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = hash('sha256', strtolower(trim($email)));

        Cache::put("otp:{$hash}", $otp, now()->addMinutes(5));

        Mail::to($email)->send(new OtpMail($otp));

        Log::info('Email OTP sent', ['email_hash' => substr($hash, 0, 8) . '…']);
    }

    public function verifikasiOTP(string $hash, string $inputOtp): bool
    {
        $cached = Cache::get("otp:{$hash}");

        if (!$cached || $cached !== $inputOtp) {
            return false;
        }

        Cache::forget("otp:{$hash}");

        EmailVerification::updateOrCreate(
            ['email_hash' => $hash],
            ['verified_at' => now(), 'trial_used_at' => now()]
        );

        return true;
    }

    public function sudahPakaiTrial(string $hash): bool
    {
        $record = EmailVerification::where('email_hash', $hash)->first();
        return $record && $record->trial_used_at !== null;
    }
}
