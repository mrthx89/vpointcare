<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleManager
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function supported(): array
    {
        return config('localization.supported', []);
    }

    public static function default(): string
    {
        $default = (string) config('localization.default', 'id');

        return self::isSupported($default) ? $default : 'id';
    }

    public static function fallback(): string
    {
        $fallback = (string) config('localization.fallback', 'en');

        return self::isSupported($fallback) ? $fallback : self::default();
    }

    public static function current(): string
    {
        $locale = App::getLocale();

        return self::isSupported($locale) ? $locale : self::default();
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::supported());
    }

    public static function normalize(?string $locale): ?string
    {
        if (! $locale) {
            return null;
        }

        $locale = strtolower(str_replace('_', '-', $locale));
        $short = substr($locale, 0, 2);

        return self::isSupported($short) ? $short : null;
    }

    public static function resolveFromRequest(Request $request): string
    {
        $sessionLocale = self::normalize(Session::get((string) config('localization.session_key', 'wacs_locale')));

        if ($sessionLocale) {
            return $sessionLocale;
        }

        $cookieLocale = self::normalize($request->cookie((string) config('localization.cookie', 'wacs_locale')));

        if ($cookieLocale) {
            return $cookieLocale;
        }

        foreach ($request->getLanguages() as $language) {
            $browserLocale = self::normalize($language);

            if ($browserLocale) {
                return $browserLocale;
            }
        }

        return self::default();
    }

    public static function label(?string $locale = null): string
    {
        $locale = $locale ?: self::current();

        return self::supported()[$locale]['label'] ?? strtoupper($locale);
    }
}
