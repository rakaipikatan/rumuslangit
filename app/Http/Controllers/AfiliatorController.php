<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Services\AffiliatePayoutService;
use App\Services\EmailOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

/**
 * Area afiliator: masuk dengan kode lewat email (tanpa password) → dashboard pribadi + panduan.
 * Sesi terpisah dari akun pelanggan (kunci sesi `affiliate_id`).
 */
class AfiliatorController extends Controller
{
    private function afiliator(): ?Affiliate
    {
        $id = Session::get('affiliate_id');
        $a = $id ? Affiliate::find($id) : null;

        return ($a && $a->isActive()) ? $a : null;
    }

    public function masukForm()
    {
        if ($this->afiliator()) {
            return redirect()->route('afiliator.dashboard');
        }

        return view('afiliator.masuk', ['emailKode' => Session::get('aff_kode_email')]);
    }

    // POST /afiliasi/masuk — kirim kode (balasan sama baik email terdaftar maupun tidak: tidak membocorkan daftar).
    public function kodeKirim(Request $request, EmailOtpService $otp)
    {
        $request->validate(['email' => 'required|email|max:255']);

        $email = strtolower(trim($request->email));
        $hash  = hash('sha256', $email);

        RateLimiter::attempt("aff-kode:{$hash}", 3, function () use ($email, $otp) {
            $ada = Affiliate::whereRaw('lower(email) = ?', [$email])->where('status', 'active')->exists();
            if ($ada) {
                try {
                    $otp->kirimOTP($email, 'login_kode');
                } catch (\Throwable $e) {
                    \Log::warning('Kode login afiliator gagal dikirim', ['error' => $e->getMessage()]);
                }
            }
        }, 600);

        Session::put('aff_kode_email', $email);

        return redirect()->route('afiliator.masuk')->with('kode_terkirim', true);
    }

    // POST /afiliasi/masuk/verifikasi
    public function kodeVerifikasi(Request $request, EmailOtpService $otp)
    {
        $request->validate(['kode_otp' => 'required|string|size:6']);

        $email = Session::get('aff_kode_email');
        $a = $email ? Affiliate::whereRaw('lower(email) = ?', [$email])->where('status', 'active')->first() : null;

        if (!$a || !$otp->periksaKode(hash('sha256', $email), $request->kode_otp)) {
            return redirect()->route('afiliator.masuk')->with('kode_terkirim', true)->withErrors(['kode_otp' => __('afiliator.kode_salah')]);
        }

        Session::forget('aff_kode_email');
        $request->session()->regenerate();
        Session::put('affiliate_id', $a->id);

        return redirect()->route('afiliator.dashboard');
    }

    public function keluar(Request $request)
    {
        Session::forget('affiliate_id');

        return redirect()->route('afiliasi.index');
    }

    public function dashboard()
    {
        $a = $this->afiliator();
        if (!$a) {
            return redirect()->route('afiliator.masuk');
        }

        $saldo = AffiliatePayoutService::saldo($a);

        $komisi = $a->commissions()->with(['order', 'user'])->latest()->limit(20)->get()->map(function ($k) {
            $fid = (int) ($k->order?->feature_id ?? 0);

            return [
                'tanggal' => $k->created_at->timezone('Asia/Jakarta')->format('d/m/Y'),
                'pengguna' => $k->user?->name ? mb_substr($k->user->name, 0, 1) . '***' : '—',
                'produk' => $fid === -1 ? __('afiliator.produk_bulanan') : ($fid === -2 ? __('afiliator.produk_tahunan') : __('afiliator.produk_satuan')),
                'jumlah' => $k->komisi_amount,
                'status' => $k->status === 'paid' ? 'dibayar' : ($k->withdrawal_id ? 'diproses' : ($k->created_at->lte(AffiliatePayoutService::batasTahan()) ? 'siap' : 'ditahan')),
            ];
        });

        // Tanggal payout berikutnya (WIB)
        $hari = (int) config('payment.affiliate_payout_day', 25);
        $now = Carbon::now('Asia/Jakarta');
        $berikut = $now->copy()->day(min($hari, $now->daysInMonth))->startOfDay();
        if ($berikut->lt($now->copy()->startOfDay())) {
            $b = $now->copy()->addMonthNoOverflow();
            $berikut = $b->copy()->day(min($hari, $b->daysInMonth))->startOfDay();
        }

        return view('afiliator.dashboard', [
            'a'          => $a,
            'saldo'      => $saldo,
            'link'       => url('/') . '?ref=' . $a->referral_code,
            'mendaftar'  => $a->users()->count(),
            'membayar'   => $a->commissions()->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'komisi'     => $komisi,
            'payouts'    => $a->withdrawals()->latest()->limit(10)->get(),
            'minimum'    => AffiliatePayoutService::minimum(),
            'berikutnya' => $berikut->locale(app()->getLocale() === 'en' ? 'en' : 'id')->isoFormat('D MMMM YYYY'),
            'bulan'      => (int) config('payment.affiliate_commission_months', 12),
        ]);
    }

    public function panduan()
    {
        $a = $this->afiliator();
        if (!$a) {
            return redirect()->route('afiliator.masuk');
        }

        return view('afiliator.panduan', [
            'p' => [
                'komisi'  => (int) $a->komisi_persen,
                'minimum' => number_format(AffiliatePayoutService::minimum(), 0, ',', '.'),
                'hari'    => (int) config('payment.affiliate_hold_days', 14),
                'tanggal' => (int) config('payment.affiliate_payout_day', 25),
                'bulan'   => (int) config('payment.affiliate_commission_months', 12),
            ],
        ]);
    }
}
