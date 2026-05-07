<?php

namespace App\Http\Middleware;

use App\Support\LocaleManager;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = LocaleManager::resolveFromRequest($request);

        App::setLocale($locale);
        Carbon::setLocale($locale);
        Number::useLocale(LocaleManager::regional($locale));
        Number::useCurrency('IDR');
        Session::put((string) config('localization.session_key', 'wacs_locale'), $locale);

        return $next($request);
    }
}
