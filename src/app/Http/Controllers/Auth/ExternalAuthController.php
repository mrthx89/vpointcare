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

            return redirect()->to(Filament::getLoginUrl())->with('external_auth_error', 'Login provider belum tersedia. Hubungi administrator.');
        }
    }

    public function callback(string $provider, Request $request, ExternalAuthService $externalAuth): RedirectResponse
    {
        $this->hitRateLimit($provider);

        try {
            $result = $externalAuth->handleCallback($provider, $request->query());

            if ($result['status'] === 'pending') {
                return redirect()->to(Filament::getLoginUrl())->with('external_auth_status', 'Pendaftaran berhasil dikirim. Admin perlu menyetujui akun Anda sebelum dapat mengakses dashboard.');
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
            abort(429, 'Terlalu banyak percobaan login. Coba lagi beberapa menit lagi.');
        }

        RateLimiter::hit($key, 300);
    }

    private function safeMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (! Str::contains($message, ['token', 'secret', 'client', 'authorization'], true) && filled($message)) {
            return $message;
        }

        return 'Login gagal. Akun belum terdaftar, belum disetujui, atau konfigurasi provider belum valid.';
    }
}
