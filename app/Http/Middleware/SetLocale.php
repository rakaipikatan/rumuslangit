<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    private const SUPPORTED = ['id', 'en'];

    public function handle(Request $request, Closure $next)
    {
        $locale = Session::get('locale', 'id');

        if (!in_array($locale, self::SUPPORTED, true)) {
            $locale = 'id';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
