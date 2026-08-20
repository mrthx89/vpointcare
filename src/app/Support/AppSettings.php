<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AppSettings
{
    private const CACHE_KEY = 'mpengaturan_aplikasi_default_v1';

    public static function all(): ?object
    {
        try {
            if (! Schema::hasTable('MPengaturanAplikasi')) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        $settings = Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function (): ?array {
            $row = DB::table('MPengaturanAplikasi')
                ->where('KodePengaturan', 'DEFAULT')
                ->where('NonAktif', false)
                ->first();

            return $row ? (array) $row : null;
        });

        return is_array($settings) ? (object) $settings : null;
    }

    public static function get(?string $key = null, mixed $default = null): mixed
    {
        $settings = self::all();

        if (! $settings) {
            return $default;
        }

        if ($key === null) {
            return $settings;
        }

        return $settings->{$key} ?? $default;
    }

    public static function brandName(): string
    {
        $name = (string) self::get('NamaAplikasi');

        return trim($name) !== '' ? $name : (string) config('app.name', 'CareDesk');
    }

    public static function tagline(): string
    {
        return (string) self::get('Tagline', 'Integrated WhatsApp & AI Helpdesk System');
    }

    public static function companyName(): string
    {
        return (string) self::get('NamaPerusahaan', 'CareDesk SaaS');
    }

    public static function logoPrimaryUrl(): string
    {
        $path = self::get('LogoUtamaPath');

        if (is_string($path) && trim($path) !== '') {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            return Storage::disk('public')->url($path);
        }

        return asset('images/logo_primary.svg');
    }

    public static function logoDarkUrl(): string
    {
        $path = self::get('LogoSekunderPath');

        if (is_string($path) && trim($path) !== '') {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            return Storage::disk('public')->url($path);
        }

        return asset('images/logo_secondary.svg');
    }

    public static function faviconUrl(): string
    {
        $path = self::get('FaviconPath');

        if (is_string($path) && trim($path) !== '') {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            return Storage::disk('public')->url($path);
        }

        return asset('images/logo_primary.svg');
    }

    public static function footerText(): string
    {
        $text = self::get('TeksFooter');

        if (is_string($text) && trim($text) !== '') {
            return $text;
        }

        return '&copy; ' . date('Y') . ' ' . self::brandName() . '. All rights reserved.';
    }

    public static function timezone(): string
    {
        return (string) self::get('ZonaWaktu', 'Asia/Jakarta');
    }

    public static function defaultLocale(): string
    {
        return (string) self::get('BahasaDefault', 'id');
    }

    public static function isSetupCompleted(): bool
    {
        return (bool) self::get('SetupSelesai', false);
    }

    public static function mailMailer(): string
    {
        return (string) self::get('MailMailer', config('mail.default', 'smtp'));
    }

    public static function mailHost(): ?string
    {
        return self::get('MailHost') ?: config('mail.mailers.smtp.host');
    }

    public static function mailPort(): int
    {
        return (int) (self::get('MailPort') ?: config('mail.mailers.smtp.port', 587));
    }

    public static function mailUsername(): ?string
    {
        return self::get('MailUsername') ?: config('mail.mailers.smtp.username');
    }

    public static function mailPassword(): ?string
    {
        $encrypted = self::get('MailPasswordTerenkripsi');
        if (! empty($encrypted)) {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($encrypted);
            } catch (\Throwable) {
                return null;
            }
        }
        return config('mail.mailers.smtp.password');
    }

    public static function mailEncryption(): ?string
    {
        return self::get('MailEncryption') ?: 'tls';
    }

    public static function mailFromAddress(): string
    {
        return (string) (self::get('MailFromAddress') ?: config('mail.from.address', 'noreply@example.com'));
    }

    public static function mailFromName(): string
    {
        return (string) (self::get('MailFromName') ?: self::brandName());
    }

    public static function applyMailConfig(): void
    {
        $settings = self::all();
        if (! $settings) {
            return;
        }

        $mailer = (string) ($settings->MailMailer ?? 'smtp');
        config(['mail.default' => $mailer]);

        if (! empty($settings->MailHost)) {
            $password = null;
            if (! empty($settings->MailPasswordTerenkripsi)) {
                try {
                    $password = \Illuminate\Support\Facades\Crypt::decryptString($settings->MailPasswordTerenkripsi);
                } catch (\Throwable) {
                    $password = null;
                }
            }

            config([
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $settings->MailHost,
                'mail.mailers.smtp.port' => (int) ($settings->MailPort ?? 587),
                'mail.mailers.smtp.encryption' => ($settings->MailEncryption ?? 'tls') === 'none' ? null : ($settings->MailEncryption ?? 'tls'),
                'mail.mailers.smtp.username' => $settings->MailUsername,
                'mail.mailers.smtp.password' => $password,
            ]);
        }

        if (! empty($settings->MailFromAddress)) {
            config([
                'mail.from.address' => $settings->MailFromAddress,
                'mail.from.name' => $settings->MailFromName ?: self::brandName(),
            ]);
        }
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
