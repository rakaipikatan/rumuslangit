<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CaptureReferral
{
    // Atribusi first-touch: kode referral pertama yang berhasil dikenali dalam
    // sesi ini dipakai untuk semua transaksi user itu ke depannya, tidak ditimpa
    // walau dia kembali lewat link afiliator lain di sesi yang sama.
    public function handle(Request $request, Closure $next)
    {
        $kode = $request->query('ref');

        if ($kode && !Session::has('ref_affiliate_id')) {
            $affiliate = Affiliate::where('referral_code', strtoupper($kode))
                ->where('status', 'active')
                ->first();

            if ($affiliate) {
                Session::put('ref_affiliate_id', $affiliate->id);
            }
        }

        return $next($request);
    }
}
