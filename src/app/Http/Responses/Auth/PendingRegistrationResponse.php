<?php

namespace App\Http\Responses\Auth;

use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class PendingRegistrationResponse implements RegistrationResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect()->to(Filament::getLoginUrl());
    }
}
