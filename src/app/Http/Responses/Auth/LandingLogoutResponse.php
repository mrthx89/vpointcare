<?php

namespace App\Http\Responses\Auth;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LandingLogoutResponse implements LogoutResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect()->to(url('/'));
    }
}
