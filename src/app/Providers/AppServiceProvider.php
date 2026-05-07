<?php

namespace App\Providers;

use App\Auth\PenggunaUserProvider;
use App\Http\Responses\Auth\LandingLogoutResponse;
use App\Support\LocaleManager;
use Carbon\Carbon;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Number;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LogoutResponse::class, LandingLogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('pengguna', fn($app, array $config): PenggunaUserProvider => new PenggunaUserProvider($app['hash'], $config['model']));

        Carbon::setLocale(config('app.locale'));
        Number::useLocale(LocaleManager::regional(config('app.locale')));
        Number::useCurrency('IDR');

        $appUrl = (string) config('app.url');
        $appUrlUsesHttps = str_starts_with($appUrl, 'https://');

        if ($appUrlUsesHttps) {
            URL::forceRootUrl($appUrl);
        }

        if (config('app.force_https') || $appUrlUsesHttps || $this->requestUsesHttpsForwarding()) {
            URL::forceScheme('https');
        }
    }

    private function requestUsesHttpsForwarding(): bool
    {
        if ($this->app->runningInConsole()) {
            return false;
        }

        $request = request();

        if ($request->headers->get('x-forwarded-proto') === 'https') {
            return true;
        }

        $cfVisitor = (string) $request->headers->get('cf-visitor', '');

        return str_contains($cfVisitor, '"scheme":"https"');
    }
}
