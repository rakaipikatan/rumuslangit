<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    private const SUPPORTED = ['id', 'en'];

    public function switch(string $locale)
    {
        abort_unless(in_array($locale, self::SUPPORTED, true), 404);

        Session::put('locale', $locale);

        return back();
    }
}
