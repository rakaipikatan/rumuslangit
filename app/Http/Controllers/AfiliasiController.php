<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use Illuminate\Http\Request;

/**
 * Halaman publik program afiliasi: info + formulir pendaftaran.
 * Pendaftar berstatus "pending" sampai admin menyetujui (tidak ada akun otomatis).
 */
class AfiliasiController extends Controller
{
    public function index()
    {
        return view('afiliasi.daftar', ['komisi' => (int) config('payment.affiliate_default_commission', 20)]);
    }

    public function daftar(Request $request)
    {
        // Honeypot: kolom tersembunyi yang hanya diisi bot. Balas seolah sukses, tanpa menyimpan.
        if (filled($request->input('website'))) {
            return redirect()->route('afiliasi.index')->with('terkirim', true);
        }

        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'email'          => 'required|email|max:255',
            'phone'          => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]{8,30}$/'],
            'promosi'        => 'nullable|string|max:1000',
            'bank_nama'      => 'required|string|max:30',
            'bank_rekening'  => ['required', 'string', 'max:50', 'regex:/^[0-9\-\s]{5,50}$/'],
            'bank_atas_nama' => 'required|string|max:100',
            'setuju'         => 'accepted',
        ]);

        $email = strtolower(trim($data['email']));

        $sudahAfiliator = Affiliate::whereRaw('lower(email) = ?', [$email])->exists();
        $sudahMenunggu  = AffiliateApplication::whereRaw('lower(email) = ?', [$email])->where('status', 'pending')->exists();

        if ($sudahAfiliator || $sudahMenunggu) {
            return back()->withInput()->withErrors(['email' => __('afiliasi.sudah_terdaftar')]);
        }

        AffiliateApplication::create([
            'name'           => $data['name'],
            'email'          => $email,
            'phone'          => $data['phone'],
            'promosi'        => $data['promosi'] ?? null,
            'bank_nama'      => $data['bank_nama'],
            'bank_rekening'  => $data['bank_rekening'],
            'bank_atas_nama' => $data['bank_atas_nama'],
            'status'         => 'pending',
        ]);

        return redirect()->route('afiliasi.index')->with('terkirim', true);
    }
}
