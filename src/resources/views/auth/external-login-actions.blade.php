@php
    $externalAuth = app(\App\Services\Auth\ExternalAuthService::class);
    $googleEnabled = $externalAuth->isEnabled('google');
    $ssoEnabled = $externalAuth->isEnabled('sso');
@endphp

@if (session('external_auth_status'))
    <div class="wacs-auth-alert wacs-auth-alert-success">{{ session('external_auth_status') }}</div>
@endif

@if (session('external_auth_error'))
    <div class="wacs-auth-alert wacs-auth-alert-danger">{{ session('external_auth_error') }}</div>
@endif

@if ($googleEnabled || $ssoEnabled)
    <div class="wacs-external-auth">
        <div class="wacs-auth-divider"><span>{{ __('ui.auth.external_login_divider') }}</span></div>

        <div class="wacs-auth-buttons">
            @if ($googleEnabled)
                <a class="wacs-auth-button wacs-auth-button-google" href="{{ route('external-auth.redirect', ['provider' => 'google']) }}">
                    <span class="wacs-auth-icon">G</span>
                    <span>{{ __('ui.auth.external_login_google') }}</span>
                </a>
            @endif

            @if ($ssoEnabled)
                <a class="wacs-auth-button wacs-auth-button-sso" href="{{ route('external-auth.redirect', ['provider' => 'sso']) }}">
                    <span class="wacs-auth-icon">SSO</span>
                    <span>{{ __('ui.auth.external_login_sso', ['provider' => config('external-auth.sso.name', __('ui.auth.sso_default_name'))]) }}</span>
                </a>
            @endif
        </div>

        <div class="wacs-auth-security-badge">{{ __('ui.auth.external_security_badge') }}</div>
    </div>
@endif


