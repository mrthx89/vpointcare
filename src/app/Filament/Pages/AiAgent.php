<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\AiSettings;
use App\Services\Ai\AiAutoReplyService;
use App\Support\FilamentAccess;
use App\Support\FilamentBreadcrumbs;
use App\Support\NavigationHelper;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AiAgent extends Page
{
    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return NavigationHelper::iconFor(AccessPermissions::AI_AGENT_VIEW, 'heroicon-o-sparkles');
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::AI_AGENT_VIEW, __('ui.navigation.assistant'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::AI_AGENT_VIEW, 10);
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('ui.pages.ai_agent.title');
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::AI_AGENT_VIEW, __('ui.pages.ai_agent.navigation_label'));
    }

    public function getBreadcrumbs(): array
    {
        return FilamentBreadcrumbs::forMenu(AccessPermissions::AI_AGENT_VIEW, __('ui.pages.ai_agent.navigation_label'));
    }

    protected string $view = 'filament.pages.ai-agent';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::AI_AGENT_VIEW)
            && NavigationHelper::isActive(AccessPermissions::AI_AGENT_VIEW);
    }

    /** @var array<string, mixed> */
    public array $pengaturan = [];

    /** @var array<string, int> */
    public array $stats = [];

    /** @var array<string, array<string, string>> */
    public array $providerPresets = [];

    public string $apiKeyBaru = '';

    public bool $apiKeyTerisi = false;

    public string $apiKeyInfo = '';

    public string $testPrompt = 'Apakah kamu sudah siap? Nama kamu siapa?';

    public string $testResult = '';

    public bool $testSedangBerjalan = false;

    public function mount(): void
    {
        $this->providerPresets = $this->providerPresets();
        $this->ensureDefaultSettings();
        $this->loadPengaturan();
    }

    public function applyProviderPreset(string $provider): void
    {
        abort_unless(FilamentAccess::can(AccessPermissions::AI_AGENT_MANAGE), 403);

        if (! array_key_exists($provider, $this->providerPresets)) {
            return;
        }

        $preset = $this->providerPresets[$provider];

        $this->pengaturan['ProviderAi'] = $provider;
        $this->pengaturan['ModelAi'] = $preset['model'];
        $this->pengaturan['BaseUrl'] = $preset['base_url'];
        if (($this->pengaturan['ModelInstructAi'] ?? '') === '') {
            $this->pengaturan['ModelInstructAi'] = $preset['model'];
        }

        $this->refreshApiKeyState();
        $this->testResult = '';
    }

    public function testKoneksiAi(): void
    {
        abort_unless(FilamentAccess::can(AccessPermissions::AI_AGENT_MANAGE), 403);

        $this->validate([
            'testPrompt' => ['required', 'string', 'max:1000'],
        ]);

        $this->testSedangBerjalan = true;
        $this->testResult = '';

        try {
            $dbSettings = DB::table('MPengaturanAi')->where('KodePengaturan', 'DEFAULT')->first();
            $provider = (string) ($this->pengaturan['ProviderAi'] ?? 'OpenAI');
            $apiKeyColumn = $this->providerApiKeyColumn($provider);
            $settings = (object) array_merge($this->pengaturan, [
                'OpenAiApiKeyTerenkripsi' => null,
                'DeepSeekApiKeyTerenkripsi' => null,
                'OpenRouterApiKeyTerenkripsi' => null,
                'NineRouterApiKeyTerenkripsi' => null,
                $apiKeyColumn => $this->apiKeyBaru !== ''
                    ? Crypt::encryptString($this->apiKeyBaru)
                    : $this->providerDbApiKey($dbSettings, $provider),
            ]);

            $this->testResult = app(AiAutoReplyService::class)->testProviderConnection($settings, $this->testPrompt);
        } catch (\Throwable $exception) {
            $this->testResult = $this->sanitizeSecretText($exception->getMessage());
        } finally {
            $this->testSedangBerjalan = false;
        }
    }

    public function simpanPengaturan(): void
    {
        abort_unless(FilamentAccess::can(AccessPermissions::AI_AGENT_MANAGE), 403);

        $validated = $this->validate([
            'pengaturan.AutoReplyAktif' => ['boolean'],
            'pengaturan.AutoReplyDiluarJamKerja' => ['boolean'],
            'pengaturan.AutoReplyHariLibur' => ['boolean'],
            'pengaturan.AutoReplyJamKerjaSapaan' => ['boolean'],
            'pengaturan.AutoReplyJamKerjaBerlanjut' => ['boolean'],
            'pengaturan.JamKerjaMulai' => ['required', 'date_format:H:i'],
            'pengaturan.JamKerjaSelesai' => ['required', 'date_format:H:i'],
            'pengaturan.HariKerja' => ['required', 'array', 'min:1'],
            'pengaturan.ZonaWaktu' => ['required', 'string', 'max:100'],
            'pengaturan.ProviderAi' => ['required', 'string', 'max:50'],
            'pengaturan.ModelAi' => ['nullable', 'string', 'max:100'],
            'pengaturan.ModelInstructAi' => ['nullable', 'string', 'max:100'],
            'pengaturan.BaseUrl' => ['nullable', 'url', 'max:255'],
            'pengaturan.PromptSistem' => ['nullable', 'string', 'max:8000'],
            'pengaturan.TemplateDiluarJamKerja' => ['nullable', 'string', 'max:4000'],
            'pengaturan.TemplateHariLibur' => ['nullable', 'string', 'max:4000'],
            'pengaturan.TemplateJamKerjaSapaan' => ['nullable', 'string', 'max:4000'],
            'pengaturan.TemplateFallback' => ['nullable', 'string', 'max:4000'],
            'pengaturan.NotifikasiChatBelumTerbalasAktif' => ['boolean'],
            'pengaturan.MenitTungguNotifikasi' => ['required', 'integer', 'min:1', 'max:1440'],
            'pengaturan.JedaNotifikasiMenit' => ['required', 'integer', 'min:1', 'max:1440'],
            'pengaturan.KodePeranPenerimaNotifikasi' => ['required', 'string', 'max:200'],
            'pengaturan.TemplateNotifikasiChatBelumTerbalas' => ['nullable', 'string', 'max:4000'],
            'pengaturan.ExcludeNomorWhatsapp' => ['nullable', 'string', 'max:4000'],
            'pengaturan.BatasRiwayatPesan' => ['required', 'integer', 'min:1', 'max:20'],
            'pengaturan.KirimKeWaha' => ['boolean'],
            'pengaturan.ModeKirim' => ['required', 'string', 'max:50'],
            'apiKeyBaru' => ['nullable', 'string', 'max:2000'],
        ]);

        $data = $validated['pengaturan'];
        $data['HariKerja'] = implode(',', $data['HariKerja']);
        $data['ExcludeNomorWhatsapp'] = $this->normalizeExcludedNumbers((string) ($data['ExcludeNomorWhatsapp'] ?? ''));
        $data = $this->normalizeProviderSettings($data);
        $data['KirimKeWaha'] = (bool) $data['KirimKeWaha'] || $data['ModeKirim'] === 'KirimWaha';
        $data['ModeKirim'] = $data['KirimKeWaha'] ? 'KirimWaha' : 'DraftLokal';
        $data['TglEdit'] = now();

        if ($this->apiKeyBaru !== '') {
            $data[$this->providerApiKeyColumn((string) $data['ProviderAi'])] = Crypt::encryptString($this->apiKeyBaru);
        }

        DB::table('MPengaturanAi')
            ->where('KodePengaturan', 'DEFAULT')
            ->update($data);

        AiSettings::flush();
        $this->apiKeyBaru = '';
        $this->loadPengaturan();

        Notification::make()
            ->title(__('ui.pages.ai_agent.settings_saved'))
            ->success()
            ->send();
    }

    public function hapusApiKey(): void
    {
        abort_unless(FilamentAccess::can(AccessPermissions::AI_AGENT_MANAGE), 403);

        $provider = (string) ($this->pengaturan['ProviderAi'] ?? 'OpenAI');

        DB::table('MPengaturanAi')
            ->where('KodePengaturan', 'DEFAULT')
            ->update([
                $this->providerApiKeyColumn($provider) => null,
                'TglEdit' => now(),
            ]);

        AiSettings::flush();
        $this->loadPengaturan();

        Notification::make()
            ->title(__('ui.pages.ai_agent.api_key_deleted'))
            ->success()
            ->send();
    }

    private function loadPengaturan(): void
    {
        $row = DB::table('MPengaturanAi')->where('KodePengaturan', 'DEFAULT')->first();

        $this->pengaturan = [
            'AutoReplyAktif' => (bool) $row->AutoReplyAktif,
            'AutoReplyDiluarJamKerja' => (bool) $row->AutoReplyDiluarJamKerja,
            'AutoReplyHariLibur' => (bool) ($row->AutoReplyHariLibur ?? true),
            'AutoReplyJamKerjaSapaan' => (bool) $row->AutoReplyJamKerjaSapaan,
            'AutoReplyJamKerjaBerlanjut' => (bool) $row->AutoReplyJamKerjaBerlanjut,
            'JamKerjaMulai' => substr((string) $row->JamKerjaMulai, 0, 5),
            'JamKerjaSelesai' => substr((string) $row->JamKerjaSelesai, 0, 5),
            'HariKerja' => array_values(array_filter(explode(',', (string) $row->HariKerja))),
            'ZonaWaktu' => $row->ZonaWaktu ?: 'Asia/Jakarta',
            'ProviderAi' => $row->ProviderAi ?: 'OpenAI',
            'ModelAi' => $row->ModelAi ?: $this->defaultModel($row->ProviderAi ?: 'OpenAI'),
            'ModelInstructAi' => $row->ModelInstructAi ?: '',
            'BaseUrl' => $row->BaseUrl ?: $this->defaultBaseUrl($row->ProviderAi ?: 'OpenAI'),
            'PromptSistem' => $row->PromptSistem,
            'TemplateDiluarJamKerja' => $row->TemplateDiluarJamKerja,
            'TemplateHariLibur' => $row->TemplateHariLibur ?? $this->defaultHolidayTemplate(),
            'TemplateJamKerjaSapaan' => $row->TemplateJamKerjaSapaan,
            'TemplateFallback' => $row->TemplateFallback,
            'NotifikasiChatBelumTerbalasAktif' => (bool) ($row->NotifikasiChatBelumTerbalasAktif ?? true),
            'MenitTungguNotifikasi' => (int) ($row->MenitTungguNotifikasi ?? 10),
            'JedaNotifikasiMenit' => (int) ($row->JedaNotifikasiMenit ?? 30),
            'KodePeranPenerimaNotifikasi' => $row->KodePeranPenerimaNotifikasi ?? 'ADMIN,SUPERVISOR_CS,CS',
            'TemplateNotifikasiChatBelumTerbalas' => $row->TemplateNotifikasiChatBelumTerbalas ?? $this->defaultNotificationTemplate(),
            'ExcludeNomorWhatsapp' => Schema::hasColumn('MPengaturanAi', 'ExcludeNomorWhatsapp') ? (string) ($row->ExcludeNomorWhatsapp ?? '') : '',
            'BatasRiwayatPesan' => (int) $row->BatasRiwayatPesan,
            'KirimKeWaha' => (bool) $row->KirimKeWaha,
            'ModeKirim' => $row->ModeKirim ?: 'DraftLokal',
        ];

        $this->refreshApiKeyState($row);

        $this->stats = [
            'chat_auto' => (int) DB::table('TChat')->where('AutoReplyAiAktif', true)->count(),
            'balasan_ai' => (int) DB::table('TChatD')->where('DihasilkanOlehAi', true)->count(),
            'permintaan_hari_ini' => (int) DB::table('TAiPermintaan')->whereDate('TglBuat', now()->toDateString())->count(),
            'hari_libur_aktif' => Schema::hasTable('MHariLibur') ? (int) DB::table('MHariLibur')->where('NonAktif', false)->count() : 0,
            'penerima_notifikasi' => (int) DB::table('MPengguna')->where('NonAktif', false)->whereNotNull('NomorWhatsappInternal')->where('NomorWhatsappInternal', '<>', '')->count(),
        ];
    }

    private function refreshApiKeyState(?object $settings = null): void
    {
        $settings ??= DB::table('MPengaturanAi')->where('KodePengaturan', 'DEFAULT')->first();

        $provider = (string) ($this->pengaturan['ProviderAi'] ?? 'OpenAI');
        $providerKey = strtolower($provider);
        $hasDbKey = filled($this->providerDbApiKey($settings, $provider));
        $hasEnvKey = filled($this->providerEnvApiKey($providerKey));

        $this->apiKeyTerisi = $hasDbKey || $hasEnvKey;

        if ($hasDbKey) {
            $this->apiKeyInfo = __('ui.pages.ai_agent.api_key_db_info');

            return;
        }

        if ($hasEnvKey) {
            $this->apiKeyInfo = __('ui.pages.ai_agent.api_key_env_info');

            return;
        }

        $this->apiKeyInfo = __('ui.pages.ai_agent.api_key_missing_info');
    }

    private function providerEnvApiKey(string $provider): ?string
    {
        return match ($provider) {
            'deepseek' => config('services.deepseek.api_key'),
            'openrouter' => config('services.openrouter.api_key'),
            '9router', 'ninerouter' => config('services.ninerouter.api_key'),
            default => config('services.openai.api_key'),
        };
    }

    private function providerDbApiKey(?object $settings, string $provider): ?string
    {
        if (! $settings) {
            return null;
        }

        $column = $this->providerApiKeyColumn($provider);

        $apiKey = $settings->{$column} ?? null;

        if ($apiKey) {
            return $apiKey;
        }

        return strtolower($provider) === 'openai'
            ? ($settings->ApiKeyTerenkripsi ?? null)
            : null;
    }

    private function providerApiKeyColumn(string $provider): string
    {
        return match (strtolower($provider)) {
            'deepseek' => 'DeepSeekApiKeyTerenkripsi',
            'openrouter' => 'OpenRouterApiKeyTerenkripsi',
            '9router', 'ninerouter' => 'NineRouterApiKeyTerenkripsi',
            default => 'OpenAiApiKeyTerenkripsi',
        };
    }

    private function ensureDefaultSettings(): void
    {
        if (DB::table('MPengaturanAi')->where('KodePengaturan', 'DEFAULT')->exists()) {
            return;
        }

        $data = [
            'Id' => (string) Str::orderedUuid(),
            'KodePengaturan' => 'DEFAULT',
            'NamaPengaturan' => 'Pengaturan Default AI Agent',
            'AutoReplyAktif' => false,
            'AutoReplyDiluarJamKerja' => true,
            'AutoReplyHariLibur' => true,
            'AutoReplyJamKerjaSapaan' => true,
            'AutoReplyJamKerjaBerlanjut' => false,
            'JamKerjaMulai' => '08:00',
            'JamKerjaSelesai' => '17:00',
            'HariKerja' => '1,2,3,4,5',
            'ZonaWaktu' => 'Asia/Jakarta',
            'ProviderAi' => 'OpenAI',
            'ModelAi' => 'gpt-5',
            'BaseUrl' => 'https://api.openai.com/v1/responses',
            'ModelInstructAi' => '',
            'PromptSistem' => 'Anda adalah AI Agent customer service VPoint Care. Jawab dalam Bahasa Indonesia yang sopan, singkat, jelas, dan jangan membuat janji teknis yang belum dipastikan.',
            'TemplateDiluarJamKerja' => 'Terima kasih sudah menghubungi VPoint Care. Saat ini kami berada di luar jam operasional. Pesan Bapak/Ibu sudah kami terima dan akan kami tindak lanjuti pada jam kerja berikutnya.',
            'TemplateHariLibur' => $this->defaultHolidayTemplate(),
            'TemplateJamKerjaSapaan' => 'Halo, terima kasih sudah menghubungi VPoint Care. Saya bantu catat terlebih dahulu ya. Silakan jelaskan kendala yang sedang dialami, nanti tim customer service kami akan melanjutkan penanganannya.',
            'TemplateFallback' => 'Terima kasih informasinya. Pesan sudah kami terima dan akan kami teruskan ke tim terkait untuk ditindaklanjuti.',
            'NotifikasiChatBelumTerbalasAktif' => true,
            'MenitTungguNotifikasi' => 10,
            'JedaNotifikasiMenit' => 30,
            'KodePeranPenerimaNotifikasi' => 'ADMIN,SUPERVISOR_CS,CS',
            'TemplateNotifikasiChatBelumTerbalas' => $this->defaultNotificationTemplate(),
            'BatasRiwayatPesan' => 8,
            'KirimKeWaha' => false,
            'ModeKirim' => 'DraftLokal',
            'NonAktif' => false,
            'TglBuat' => now(),
        ];

        if (Schema::hasColumn('MPengaturanAi', 'ExcludeNomorWhatsapp')) {
            $data['ExcludeNomorWhatsapp'] = '';
        }

        DB::table('MPengaturanAi')->insert($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeProviderSettings(array $data): array
    {
        $provider = strtolower((string) ($data['ProviderAi'] ?? 'OpenAI'));
        $baseUrl = (string) ($data['BaseUrl'] ?? '');
        $model = (string) ($data['ModelAi'] ?? '');

        if (in_array($provider, ['deepseek', 'openrouter', '9router', 'ninerouter'], true)) {
            $service = match ($provider) {
                'openrouter' => 'openrouter',
                '9router', 'ninerouter' => 'ninerouter',
                default => 'deepseek',
            };

            if ($baseUrl === '' || str_contains($baseUrl, 'api.openai.com') || (in_array($provider, ['openrouter', '9router', 'ninerouter'], true) && str_contains($baseUrl, 'api.deepseek.com')) || ($provider === 'deepseek' && (str_contains($baseUrl, 'openrouter.ai') || str_contains($baseUrl, '9router')))) {
                $data['BaseUrl'] = config("services.{$service}.base_url");
            }

            $data['ModelInstructAi'] = trim((string) ($data['ModelInstructAi'] ?? ''));

            if ($model === '' || str_starts_with($model, 'gpt-') || (in_array($provider, ['openrouter', '9router', 'ninerouter'], true) && str_starts_with($model, 'deepseek-')) || ($provider === 'deepseek' && str_contains($model, '/'))) {
                $data['ModelAi'] = config("services.{$service}.model");
            }

            return $data;
        }

        if ($baseUrl === '' || str_contains($baseUrl, 'api.deepseek.com') || str_contains($baseUrl, 'openrouter.ai')) {
            $data['BaseUrl'] = config('services.openai.base_url');
        }

        $data['ModelInstructAi'] = trim((string) ($data['ModelInstructAi'] ?? ''));

        if ($model === '' || str_starts_with($model, 'deepseek-') || str_contains($model, '/')) {
            $data['ModelAi'] = config('services.openai.model');
        }

        return $data;
    }

    private function defaultModel(string $provider): string
    {
        return match (strtolower($provider)) {
            'deepseek' => (string) config('services.deepseek.model'),
            'openrouter' => (string) config('services.openrouter.model'),
            '9router', 'ninerouter' => (string) config('services.ninerouter.model'),
            default => (string) config('services.openai.model'),
        };
    }

    private function defaultBaseUrl(string $provider): string
    {
        return match (strtolower($provider)) {
            'deepseek' => (string) config('services.deepseek.base_url'),
            'openrouter' => (string) config('services.openrouter.base_url'),
            '9router', 'ninerouter' => (string) config('services.ninerouter.base_url'),
            default => (string) config('services.openai.base_url'),
        };
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function providerPresets(): array
    {
        return [
            'OpenAI' => [
                'label' => 'OpenAI',
                'summary' => 'Stabil untuk customer service.',
                'model' => (string) config('services.openai.model'),
                'base_url' => (string) config('services.openai.base_url'),
                'key_label' => 'OPENAI_API_KEY',
                'icon_text' => 'AI',
                'icon_path' => 'images/ai-provider-openai-2.svg',
                'icon_class' => 'bg-black text-white ring-1 ring-gray-300 dark:bg-white dark:text-black dark:ring-white/20',
            ],
            'DeepSeek' => [
                'label' => 'DeepSeek',
                'summary' => 'Alternatif hemat dengan API sendiri.',
                'model' => (string) config('services.deepseek.model'),
                'base_url' => (string) config('services.deepseek.base_url'),
                'key_label' => 'DEEPSEEK_API_KEY',
                'icon_text' => 'DS',
                'icon_path' => 'images/ai-provider-deepseek-2.svg',
                'icon_class' => 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white',
            ],
            'OpenRouter' => [
                'label' => 'OpenRouter',
                'summary' => 'Router banyak model, termasuk opsi free.',
                'model' => (string) config('services.openrouter.model'),
                'base_url' => (string) config('services.openrouter.base_url'),
                'key_label' => 'OPENROUTER_API_KEY',
                'icon_text' => 'OR',
                'icon_path' => 'images/ai-provider-openrouter-2.svg',
                'icon_class' => 'bg-gradient-to-br from-slate-900 to-sky-600 text-white dark:from-sky-500 dark:to-cyan-400 dark:text-slate-950',
            ],
            '9Router' => [
                'label' => '9Router',
                'summary' => 'Preset 9Router dengan format chat completions.',
                'model' => (string) config('services.ninerouter.model'),
                'base_url' => (string) config('services.ninerouter.base_url'),
                'key_label' => 'NINEROUTER_API_KEY',
                'icon_text' => '9R',
                'icon_path' => 'images/ai-provider-9router-2.svg',
                'icon_class' => 'bg-gradient-to-br from-orange-500 to-amber-400 text-white',
            ],
        ];
    }

    private function defaultNotificationTemplate(): string
    {
        return 'Halo {nama_user}, ada chat WhatsApp dari {nama_instansi} yang belum dibalas selama {menit_menunggu} menit. Kontak: {nama_kontak} ({nomor_whatsapp}). Pesan terakhir: {pesan_terakhir}. Silakan cek VPoint Care: {url_admin}';
    }

    private function defaultHolidayTemplate(): string
    {
        return 'Terima kasih sudah menghubungi VPoint Care. Hari ini kami sedang libur ({nama_hari_libur}). Pesan Bapak/Ibu tetap kami terima dan akan kami teruskan ke tim customer service. Silakan sampaikan detail kendalanya agar tim kami bisa menindaklanjuti pada hari kerja berikutnya, {tanggal_masuk_kerja}. Mohon maaf atas ketidaknyamanannya.';
    }

    private function sanitizeSecretText(string $text): string
    {
        foreach ([$this->apiKeyBaru, (string) config('services.openai.api_key'), (string) config('services.deepseek.api_key'), (string) config('services.openrouter.api_key'), (string) config('services.ninerouter.api_key')] as $secret) {
            if ($secret !== '') {
                $text = str_replace($secret, '[secret]', $text);
            }
        }

        return $text;
    }

    private function normalizeExcludedNumbers(string $value): string
    {
        $numbers = preg_split('/[\s,;]+/', $value) ?: [];

        return collect($numbers)
            ->map(fn (string $number): string => preg_replace('/@.+$/', '', trim($number)) ?: trim($number))
            ->map(fn (string $number): ?string => preg_replace('/[^0-9]/', '', $number) ?: null)
            ->filter()
            ->unique()
            ->implode(PHP_EOL);
    }
}
