<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\ExternalAuthService;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class ExternalAuthController extends Controller
{
    public function redirect(string $provider, ExternalAuthService $externalAuth): RedirectResponse
    {
        $this->hitRateLimit($provider);

        try {
            return redirect()->away($externalAuth->redirectUrl($provider));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->to(Filament::getLoginUrl())->with('external_auth_error', __('ui.auth.external_provider_unavailable'));
        }
    }

    public function callback(string $provider, Request $request, ExternalAuthService $externalAuth): RedirectResponse
    {
        $this->hitRateLimit($provider);

        try {
            $result = $externalAuth->handleCallback($provider, $request->query());

            if ($result['status'] === 'pending') {
                return redirect()->to(Filament::getLoginUrl())->with('external_auth_status', __('ui.auth.external_pending'));
            }

            return redirect()->intended(Filament::getUrl());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->to(Filament::getLoginUrl())->with('external_auth_error', $this->safeMessage($exception));
        }
    }

    private function hitRateLimit(string $provider): void
    {
        $key = 'external-auth:' . $provider . ':' . request()->ip();
        $limit = (int) config('external-auth.rate_limit', 10);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            abort(429, __('ui.auth.external_rate_limited'));
        }

        RateLimiter::hit($key, 300);
    }

    private function safeMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (! Str::contains($message, ['token', 'secret', 'client', 'authorization'], true) && filled($message)) {
            return $message;
        }

        return __('ui.auth.external_failed');
    }
}

