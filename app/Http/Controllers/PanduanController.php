<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;

/** Panduan pengguna — hanya untuk yang sudah login (punya sesi pengguna). */
class PanduanController extends Controller
{
    public function index()
    {
        if (!Session::get('user_id')) {
            return redirect()->route('auth.login.form');
        }

        $rp = fn (int $n) => number_format($n, 0, ',', '.');

        $p = [
            'satuan'   => $rp(29000),
            'n'        => (int) config('payment.single_generations', 3),
            'bulanan'  => $rp((int) config('payment.subscription_monthly_price')),
            'tahunan'  => $rp((int) config('payment.subscription_yearly_price')),
            'batas'    => (int) config('payment.subscriber_daily_reports', 20),
            'gratis'   => (int) config('payment.free_daily_reports', 5),
        ];

        return view('panduan.index', compact('p'));
    }
}
