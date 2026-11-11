<?php

namespace App\Http\Controllers;

/** Halaman legal publik (Privasi, Syarat, Refund) — isi di berkas bahasa legal.php. */
class HalamanController extends Controller
{
    private function tampil(string $kunci)
    {
        return view('legal.halaman', ['kunci' => $kunci]);
    }

    public function privasi() { return $this->tampil('privasi'); }

    public function syarat() { return $this->tampil('syarat'); }

    public function refund() { return $this->tampil('refund'); }
}
