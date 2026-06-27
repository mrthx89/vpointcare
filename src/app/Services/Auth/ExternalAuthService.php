<?php

namespace App\Services\Auth;

use App\Models\Auth\PenggunaExternalIdentity;
use App\Models\Master\Pengguna;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ExternalAuthService
{
    public function redirectUrl(string $provider): string
    {
        $config = $this->providerConfig($provider);

        if (! $this->isEnabled($provider)) {
            throw new RuntimeException(__('ui.auth.external_provider_disabled'));
        }

        $state = Str::random(48);
        $nonce = Str::random(48);

        session([
            "external_auth.{$provider}.state" => $state,
            "external_auth.{$provider}.nonce" => $nonce,
        ]);

        return $this->authorizeUrl($provider, $config) . '?' . http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array{status: string, user?: Pengguna}
     */
    public function handleCallback(string $provider, array $query): array
    {
        $config = $this->providerConfig($provider);

        if (! $this->isEnabled($provider)) {
            throw new RuntimeException(__('ui.auth.external_provider_disabled'));
        }

        $this->validateCallbackState($provider, $query);

        $profile = $this->fetchProfile($provider, $config, (string) $query['code']);
        $this->validateProfile($provider, $profile, $config);

        return DB::transaction(function () use ($provider, $profile): array {
            $identity = PenggunaExternalIdentity::query()
                ->where('Provider', $provider)
                ->where('ProviderUserId', $profile['subject'])
                ->lockForUpdate()
                ->first();

            if ($identity) {
                $user = $identity->pengguna()->lockForUpdate()->firstOrFail();
                $this->assertApprovedUser($user);
                $this->touchIdentity($identity, $profile);
                $this->login($user);
                $this->audit('external_login_success', $provider, $profile['email'], $user);

                return ['status' => 'authenticated', 'user' => $user];
            }

            $user = Pengguna::query()
                ->where('Email', $profile['email'])
                ->lockForUpdate()
                ->first();

            if ($user) {
                $this->assertApprovedUser($user);
                $this->linkIdentity($user, $provider, $profile);
                $this->login($user);
                $this->audit('external_login_linked', $provider, $profile['email'], $user);

                return ['status' => 'authenticated', 'user' => $user];
            }

            if (! (bool) config('external-auth.registration_enabled', true)) {
                $this->audit('external_registration_disabled', $provider, $profile['email']);
                throw new RuntimeException(__('ui.auth.external_registration_disabled'));
            }

            $user = $this->createPendingUser($provider, $profile);
            $this->linkIdentity($user, $provider, $profile);
            $this->audit('external_registration_pending', $provider, $profile['email'], $user);

            return ['status' => 'pending', 'user' => $user];
        });
    }

    public function isEnabled(string $provider): bool
    {
        $config = $this->providerConfig($provider);

        return (bool) ($config['enabled'] ?? false)
            && filled($config['client_id'] ?? null)
            && filled($config['client_secret'] ?? null)
            && filled($config['redirect_uri'] ?? null);
    }

    public function hasEnabledProviders(): bool
    {
        return $this->isEnabled('google') || $this->isEnabled('sso');
    }

    /**
     * @return array<string, mixed>
     */
    private function providerConfig(string $provider): array
    {
        if (! in_array($provider, ['google', 'sso'], true)) {
            throw new RuntimeException(__('ui.auth.external_provider_unknown'));
        }

        return (array) config("external-auth.{$provider}", []);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function authorizeUrl(string $provider, array $config): string
    {
        if ($provider === 'google') {
            return (string) $config['authorize_url'];
        }

        return (string) ($config['authorize_url'] ?: rtrim((string) $config['issuer_url'], '/') . '/authorize');
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function validateCallbackState(string $provider, array $query): void
    {
        if (isset($query['error'])) {
            throw new RuntimeException(__('ui.auth.external_provider_rejected'));
        }

        $state = session()->pull("external_auth.{$provider}.state");
        session()->pull("external_auth.{$provider}.nonce");

        if (! filled($query['code'] ?? null) || ! filled($query['state'] ?? null) || ! is_string($state) || ! hash_equals($state, (string) $query['state'])) {
            $this->audit('external_login_invalid_state', $provider);
            throw new RuntimeException(__('ui.auth.external_invalid_state'));
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{subject: string, email: string, email_verified: bool, name: string, avatar_url: ?string, raw: array<string, mixed>}
     */
    private function fetchProfile(string $provider, array $config, string $code): array
    {
        try {
            $tokenResponse = Http::asForm()->timeout(10)->post($this->tokenUrl($provider, $config), [
                'grant_type' => 'authorization_code',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri' => $config['redirect_uri'],
                'code' => $code,
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(__('ui.auth.external_provider_unreachable'));
        }

        if (! $tokenResponse->successful()) {
            $this->audit('external_token_failed', $provider);
            throw new RuntimeException(__('ui.auth.external_invalid_code'));
        }

        $accessToken = (string) Arr::get($tokenResponse->json(), 'access_token');

        if (! filled($accessToken)) {
            throw new RuntimeException(__('ui.auth.external_invalid_token'));
        }

        try {
            $profileResponse = Http::withToken($accessToken)->timeout(10)->get($this->userinfoUrl($provider, $config));
        } catch (ConnectionException $exception) {
            throw new RuntimeException(__('ui.auth.external_profile_unreachable'));
        }

        if (! $profileResponse->successful()) {
            $this->audit('external_userinfo_failed', $provider);
            throw new RuntimeException(__('ui.auth.external_invalid_profile'));
        }

        $raw = (array) $profileResponse->json();

        return [
            'subject' => (string) ($raw['sub'] ?? $raw['id'] ?? ''),
            'email' => Str::lower((string) ($raw['email'] ?? '')),
            'email_verified' => filter_var($raw['email_verified'] ?? true, FILTER_VALIDATE_BOOL),
            'name' => (string) ($raw['name'] ?? $raw['preferred_username'] ?? $raw['email'] ?? 'User'),
            'avatar_url' => filled($raw['picture'] ?? null) ? (string) $raw['picture'] : null,
            'raw' => Arr::only($raw, ['iss', 'sub', 'email', 'email_verified', 'name', 'preferred_username', 'picture']),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function tokenUrl(string $provider, array $config): string
    {
        if ($provider === 'google') {
            return (string) $config['token_url'];
        }

        return (string) ($config['token_url'] ?: rtrim((string) $config['issuer_url'], '/') . '/token');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function userinfoUrl(string $provider, array $config): string
    {
        if ($provider === 'google') {
            return (string) $config['userinfo_url'];
        }

        return (string) ($config['userinfo_url'] ?: rtrim((string) $config['issuer_url'], '/') . '/userinfo');
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array{subject: string, email: string, email_verified: bool, name: string, avatar_url: ?string, raw: array<string, mixed>}  $profile
     */
    private function validateProfile(string $provider, array $profile, array $config): void
    {
        if (! filled($profile['subject']) || ! filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(__('ui.auth.external_invalid_email_profile'));
        }

        if (! $profile['email_verified']) {
            throw new RuntimeException(__('ui.auth.external_email_unverified'));
        }

        $domains = array_map('strtolower', (array) ($config['allowed_domains'] ?? []));
        $domain = Str::after($profile['email'], '@');

        if ($domains !== [] && ! in_array($domain, $domains, true)) {
            $this->audit('external_domain_denied', $provider, $profile['email']);
            throw new RuntimeException(__('ui.auth.external_domain_denied'));
        }
    }

    /**
     * @param  array{subject: string, email: string, email_verified: bool, name: string, avatar_url: ?string, raw: array<string, mixed>}  $profile
     */
    private function createPendingUser(string $provider, array $profile): Pengguna
    {
        return Pengguna::query()->forceCreate([
            'Id' => (string) Str::uuid(),
            'NamaPengguna' => Str::limit($profile['name'], 150, ''),
            'Email' => $profile['email'],
            'Password' => Hash::make(Str::random(64)),
            'IdPeran' => null,
            'EmailTerverifikasiPada' => now(),
            'NonAktif' => true,
            'StatusRegistrasi' => 'pending',
            'RegistrasiExternalProvider' => $provider,
            'RegistrasiExternalPada' => now(),
        ]);
    }

    /**
     * @param  array{subject: string, email: string, email_verified: bool, name: string, avatar_url: ?string, raw: array<string, mixed>}  $profile
     */
    private function linkIdentity(Pengguna $user, string $provider, array $profile): PenggunaExternalIdentity
    {
        return PenggunaExternalIdentity::query()->forceCreate([
            'Id' => (string) Str::uuid(),
            'IdPengguna' => $user->getKey(),
            'Provider' => $provider,
            'ProviderUserId' => $profile['subject'],
            'Email' => $profile['email'],
            'EmailTerverifikasi' => $profile['email_verified'],
            'Nama' => Str::limit($profile['name'], 150, ''),
            'AvatarUrl' => $profile['avatar_url'],
            'Metadata' => $profile['raw'],
            'TglTaut' => now(),
            'LoginTerakhirPada' => now(),
        ]);
    }

    /**
     * @param  array{subject: string, email: string, email_verified: bool, name: string, avatar_url: ?string, raw: array<string, mixed>}  $profile
     */
    private function touchIdentity(PenggunaExternalIdentity $identity, array $profile): void
    {
        $identity->forceFill([
            'Email' => $profile['email'],
            'EmailTerverifikasi' => $profile['email_verified'],
            'Nama' => Str::limit($profile['name'], 150, ''),
            'AvatarUrl' => $profile['avatar_url'],
            'Metadata' => $profile['raw'],
            'LoginTerakhirPada' => now(),
            'TglEdit' => now(),
        ])->save();
    }

    private function assertApprovedUser(Pengguna $user): void
    {
        if ((bool) $user->NonAktif || ! filled($user->IdPeran) || ! $user->roleCode() || (($user->StatusRegistrasi ?? 'approved') !== 'approved')) {
            throw new RuntimeException(__('ui.auth.external_not_approved'));
        }
    }

    private function login(Pengguna $user): void
    {
        Auth::login($user);
        session()->regenerate();
        $user->forceFill(['LoginTerakhirPada' => now()])->save();
    }

    private function audit(string $event, string $provider, ?string $email = null, ?Pengguna $user = null): void
    {
        Log::info('external_auth_event', [
            'event' => $event,
            'provider' => $provider,
            'email_hash' => $email ? hash('sha256', Str::lower($email)) : null,
            'pengguna_id' => $user?->getKey(),
            'ip' => request()?->ip(),
        ]);
    }
}



