<?php

namespace App\Http\Controllers;

use App\Data\TarotData;

class TarotController extends Controller
{
    public function pilih(\Illuminate\Http\Request $request)
    {
        $kartu = TarotData::semua();
        $back  = $request->query('back'); // featureId untuk redirect kembali ke form
        return view('tarot.pilih', compact('kartu', 'back'));
    }
}
