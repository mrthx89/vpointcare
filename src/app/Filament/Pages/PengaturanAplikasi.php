<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\AppSettings;
use App\Support\FilamentAccess;
use App\Support\FilamentBreadcrumbs;
use App\Support\NavigationHelper;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class PengaturanAplikasi extends Page
{
    use WithFileUploads;

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return NavigationHelper::iconFor(AccessPermissions::APP_SETTINGS_VIEW, 'heroicon-o-cog-6-tooth');
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::APP_SETTINGS_VIEW, __('ui.navigation.settings'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::APP_SETTINGS_VIEW, 5);
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('ui.pages.app_settings.title');
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::APP_SETTINGS_VIEW, __('ui.pages.app_settings.navigation_label'));
    }

    public function getBreadcrumbs(): array
    {
        return FilamentBreadcrumbs::forMenu(AccessPermissions::APP_SETTINGS_VIEW, __('ui.pages.app_settings.navigation_label'));
    }

    protected string $view = 'filament.pages.pengaturan-aplikasi';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::APP_SETTINGS_VIEW)
            && NavigationHelper::isActive(AccessPermissions::APP_SETTINGS_VIEW);
    }

    public string $activeTab = 'branding';

    /** @var array<string, mixed> */
    public array $pengaturan = [];

    public string $mailPasswordBaru = '';

    public bool $mailPasswordTerisi = false;

    public string $testEmailRecipient = '';

    public bool $testEmailSedangBerjalan = false;

    public string $testEmailResult = '';

    /** @var mixed */
    public $logoUtamaFile = null;

    /** @var mixed */
    public $logoSekunderFile = null;

    /** @var mixed */
    public $faviconFile = null;

    public function mount(): void
    {
        $this->ensureDefaultSettings();
        $this->loadPengaturan();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function loadPengaturan(): void
    {
        if (! Schema::hasTable('MPengaturanAplikasi')) {
            return;
        }

        $row = DB::table('MPengaturanAplikasi')
            ->where('KodePengaturan', 'DEFAULT')
            ->first();

        if (! $row) {
            $this->ensureDefaultSettings();
            $row = DB::table('MPengaturanAplikasi')->where('KodePengaturan', 'DEFAULT')->first();
        }

        $this->mailPasswordTerisi = ! empty($row->MailPasswordTerenkripsi);

        $this->pengaturan = [
            'NamaAplikasi' => (string) ($row->NamaAplikasi ?? config('app.name', 'CareDesk')),
            'Tagline' => (string) ($row->Tagline ?? ''),
            'NamaPerusahaan' => (string) ($row->NamaPerusahaan ?? ''),
            'LogoUtamaPath' => (string) ($row->LogoUtamaPath ?? ''),
            'LogoSekunderPath' => (string) ($row->LogoSekunderPath ?? ''),
            'FaviconPath' => (string) ($row->FaviconPath ?? ''),
            'TeksFooter' => (string) ($row->TeksFooter ?? ''),
            'BahasaDefault' => (string) ($row->BahasaDefault ?? 'id'),
            'ZonaWaktu' => (string) ($row->ZonaWaktu ?? 'Asia/Jakarta'),
            'FormatTanggal' => (string) ($row->FormatTanggal ?? 'd/m/Y'),
            'EmailSupport' => (string) ($row->EmailSupport ?? ''),
            'NomorTeleponSupport' => (string) ($row->NomorTeleponSupport ?? ''),
            'AlamatKantor' => (string) ($row->AlamatKantor ?? ''),
            'MailMailer' => (string) ($row->MailMailer ?? 'smtp'),
            'MailHost' => (string) ($row->MailHost ?? ''),
            'MailPort' => (int) ($row->MailPort ?? 587),
            'MailUsername' => (string) ($row->MailUsername ?? ''),
            'MailEncryption' => (string) ($row->MailEncryption ?? 'tls'),
            'MailFromAddress' => (string) ($row->MailFromAddress ?? ''),
            'MailFromName' => (string) ($row->MailFromName ?? ''),
        ];
    }

    public function simpanPengaturan(): void
    {
        abort_unless(FilamentAccess::can(AccessPermissions::APP_SETTINGS_MANAGE), 403);

        $this->validate([
            'pengaturan.NamaAplikasi' => ['required', 'string', 'max:100'],
            'pengaturan.Tagline' => ['nullable', 'string', 'max:255'],
            'pengaturan.NamaPerusahaan' => ['nullable', 'string', 'max:200'],
            'pengaturan.TeksFooter' => ['nullable', 'string', 'max:1000'],
            'pengaturan.BahasaDefault' => ['required', 'string', 'in:id,en'],
            'pengaturan.ZonaWaktu' => ['required', 'string', 'max:100'],
            'pengaturan.FormatTanggal' => ['required', 'string', 'max:50'],
            'pengaturan.EmailSupport' => ['nullable', 'email', 'max:150'],
            'pengaturan.NomorTeleponSupport' => ['nullable', 'string', 'max:50'],
            'pengaturan.AlamatKantor' => ['nullable', 'string', 'max:500'],
            'pengaturan.MailMailer' => ['required', 'string', 'in:smtp,sendmail,log'],
            'pengaturan.MailHost' => ['nullable', 'string', 'max:255'],
            'pengaturan.MailPort' => ['required', 'integer', 'min:1', 'max:65535'],
            'pengaturan.MailUsername' => ['nullable', 'string', 'max:255'],
            'pengaturan.MailEncryption' => ['required', 'string', 'in:tls,ssl,none'],
            'pengaturan.MailFromAddress' => ['nullable', 'email', 'max:255'],
            'pengaturan.MailFromName' => ['nullable', 'string', 'max:255'],
            'mailPasswordBaru' => ['nullable', 'string', 'max:500'],
            'logoUtamaFile' => ['nullable', 'image', 'max:2048'],
            'logoSekunderFile' => ['nullable', 'image', 'max:2048'],
            'faviconFile' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:1024'],
        ]);

        if ($this->logoUtamaFile) {
            $path = $this->logoUtamaFile->store('brand', 'public');
            $this->pengaturan['LogoUtamaPath'] = $path;
            $this->logoUtamaFile = null;
        }

        if ($this->logoSekunderFile) {
            $path = $this->logoSekunderFile->store('brand', 'public');
            $this->pengaturan['LogoSekunderPath'] = $path;
            $this->logoSekunderFile = null;
        }

        if ($this->faviconFile) {
            $path = $this->faviconFile->store('brand', 'public');
            $this->pengaturan['FaviconPath'] = $path;
            $this->faviconFile = null;
        }

        $dataToSave = [
            'NamaAplikasi' => trim($this->pengaturan['NamaAplikasi']),
            'Tagline' => trim($this->pengaturan['Tagline']),
            'NamaPerusahaan' => trim($this->pengaturan['NamaPerusahaan']),
            'LogoUtamaPath' => $this->pengaturan['LogoUtamaPath'] ?: null,
            'LogoSekunderPath' => $this->pengaturan['LogoSekunderPath'] ?: null,
            'FaviconPath' => $this->pengaturan['FaviconPath'] ?: null,
            'TeksFooter' => trim($this->pengaturan['TeksFooter']),
            'BahasaDefault' => $this->pengaturan['BahasaDefault'],
            'ZonaWaktu' => $this->pengaturan['ZonaWaktu'],
            'FormatTanggal' => $this->pengaturan['FormatTanggal'],
            'EmailSupport' => trim($this->pengaturan['EmailSupport']) ?: null,
            'NomorTeleponSupport' => trim($this->pengaturan['NomorTeleponSupport']) ?: null,
            'AlamatKantor' => trim($this->pengaturan['AlamatKantor']) ?: null,
            'MailMailer' => $this->pengaturan['MailMailer'],
            'MailHost' => trim($this->pengaturan['MailHost']) ?: null,
            'MailPort' => (int) $this->pengaturan['MailPort'],
            'MailUsername' => trim($this->pengaturan['MailUsername']) ?: null,
            'MailEncryption' => $this->pengaturan['MailEncryption'],
            'MailFromAddress' => trim($this->pengaturan['MailFromAddress']) ?: null,
            'MailFromName' => trim($this->pengaturan['MailFromName']) ?: null,
            'NonAktif' => false,
            'TglEdit' => now(),
        ];

        if (trim($this->mailPasswordBaru) !== '') {
            $dataToSave['MailPasswordTerenkripsi'] = Crypt::encryptString(trim($this->mailPasswordBaru));
            $this->mailPasswordBaru = '';
            $this->mailPasswordTerisi = true;
        }

        DB::table('MPengaturanAplikasi')
            ->where('KodePengaturan', 'DEFAULT')
            ->update($dataToSave);

        AppSettings::flush();
        AppSettings::applyMailConfig();
        NavigationHelper::flush();

        Notification::make()
            ->title(__('ui.pages.app_settings.saved_title'))
            ->body(__('ui.pages.app_settings.saved_body'))
            ->success()
            ->send();
    }

    public function testKirimEmail(): void
    {
        abort_unless(FilamentAccess::can(AccessPermissions::APP_SETTINGS_MANAGE), 403);

        $this->validate([
            'testEmailRecipient' => ['required', 'email', 'max:255'],
        ]);

        $this->testEmailSedangBerjalan = true;
        $this->testEmailResult = '';

        try {
            // Apply transient mail settings
            $host = trim($this->pengaturan['MailHost']);
            $port = (int) $this->pengaturan['MailPort'];
            $username = trim($this->pengaturan['MailUsername']);
            $encryption = $this->pengaturan['MailEncryption'] === 'none' ? null : $this->pengaturan['MailEncryption'];
            $fromAddress = trim($this->pengaturan['MailFromAddress']) ?: ($username ?: 'noreply@example.com');
            $fromName = trim($this->pengaturan['MailFromName']) ?: trim($this->pengaturan['NamaAplikasi']);

            $password = null;
            if (trim($this->mailPasswordBaru) !== '') {
                $password = trim($this->mailPasswordBaru);
            } else {
                $password = AppSettings::mailPassword();
            }

            if ($this->pengaturan['MailMailer'] === 'smtp' && empty($host)) {
                throw new \InvalidArgumentException(__('ui.pages.app_settings.mail_host_required'));
            }

            config([
                'mail.default' => $this->pengaturan['MailMailer'],
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.encryption' => $encryption,
                'mail.mailers.smtp.username' => $username,
                'mail.mailers.smtp.password' => $password,
                'mail.from.address' => $fromAddress,
                'mail.from.name' => $fromName,
            ]);

            // Clear mailer instance to pick up new config
            app()->forgetInstance('mailer');

            $appName = trim($this->pengaturan['NamaAplikasi']) ?: config('app.name', 'CareDesk');
            $recipient = trim($this->testEmailRecipient);

            Mail::raw(
                __('ui.pages.app_settings.test_mail_body', [
                    'app' => $appName,
                    'time' => now()->toDateTimeString(),
                    'host' => $host ?: 'localhost',
                ]),
                function ($message) use ($recipient, $appName) {
                    $message->to($recipient)
                        ->subject(__('ui.pages.app_settings.test_mail_subject', ['app' => $appName]));
                }
            );

            $this->testEmailResult = __('ui.pages.app_settings.test_mail_success', ['email' => $recipient]);

            Notification::make()
                ->title(__('ui.pages.app_settings.test_mail_success_title'))
                ->body(__('ui.pages.app_settings.test_mail_success', ['email' => $recipient]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->testEmailResult = 'ERROR: ' . $e->getMessage();

            Notification::make()
                ->title(__('ui.pages.app_settings.test_mail_failed_title'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->testEmailSedangBerjalan = false;
        }
    }

    public function hapusLogo(string $tipe): void
    {
        abort_unless(FilamentAccess::can(AccessPermissions::APP_SETTINGS_MANAGE), 403);

        $column = match ($tipe) {
            'utama' => 'LogoUtamaPath',
            'sekunder' => 'LogoSekunderPath',
            'favicon' => 'FaviconPath',
            default => null,
        };

        if (! $column) {
            return;
        }

        $currentPath = (string) ($this->pengaturan[$column] ?? '');

        if ($currentPath !== '' && Storage::disk('public')->exists($currentPath)) {
            Storage::disk('public')->delete($currentPath);
        }

        $this->pengaturan[$column] = '';

        DB::table('MPengaturanAplikasi')
            ->where('KodePengaturan', 'DEFAULT')
            ->update([
                $column => null,
                'TglEdit' => now(),
            ]);

        AppSettings::flush();

        Notification::make()
            ->title(__('ui.pages.app_settings.logo_deleted'))
            ->success()
            ->send();
    }

    private function ensureDefaultSettings(): void
    {
        if (! Schema::hasTable('MPengaturanAplikasi')) {
            return;
        }

        $exists = DB::table('MPengaturanAplikasi')->where('KodePengaturan', 'DEFAULT')->exists();

        if (! $exists) {
            DB::table('MPengaturanAplikasi')->insert([
                'Id' => (string) Str::uuid(),
                'KodePengaturan' => 'DEFAULT',
                'NamaAplikasi' => config('app.name', 'CareDesk'),
                'Tagline' => 'Integrated WhatsApp & AI Helpdesk System',
                'NamaPerusahaan' => 'CareDesk SaaS',
                'TeksFooter' => 'Care Desk System. All rights reserved.',
                'BahasaDefault' => 'id',
                'ZonaWaktu' => 'Asia/Jakarta',
                'FormatTanggal' => 'd/m/Y',
                'MailMailer' => 'smtp',
                'MailPort' => 587,
                'MailEncryption' => 'tls',
                'SetupSelesai' => false,
                'NonAktif' => false,
                'TglBuat' => now(),
                'TglEdit' => now(),
            ]);
        }
    }
}
