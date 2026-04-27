<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

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
