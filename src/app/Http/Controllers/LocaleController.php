<?php

namespace App\Http\Controllers;

use App\Support\LocaleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $locale = LocaleManager::normalize($locale) ?? LocaleManager::default();
        $sessionKey = (string) config('localization.session_key', 'wacs_locale');
        $cookieName = (string) config('localization.cookie', 'wacs_locale');

        App::setLocale($locale);
        Session::put($sessionKey, $locale);
        Cookie::queue(cookie($cookieName, $locale, 60 * 24 * 365));

        return redirect()->back();
    }
}
