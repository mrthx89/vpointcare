<?php

namespace App\Filament\Pages;

use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AiAgent extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';

    protected static string | \UnitEnum | null $navigationGroup = 'Asisten';

    protected static ?string $navigationLabel = 'AI Agent';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'AI Agent';

    protected string $view = 'filament.pages.ai-agent';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::AI_AGENT_VIEW);
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

        $this->refreshApiKeyState();
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
            'pengaturan.BatasRiwayatPesan' => ['required', 'integer', 'min:1', 'max:20'],
            'pengaturan.KirimKeWaha' => ['boolean'],
            'pengaturan.ModeKirim' => ['required', 'string', 'max:50'],
            'apiKeyBaru' => ['nullable', 'string', 'max:2000'],
        ]);

        $data = $validated['pengaturan'];
        $data['HariKerja'] = implode(',', $data['HariKerja']);
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
            default => 'OpenAiApiKeyTerenkripsi',
        };
    }

    private function ensureDefaultSettings(): void
    {
        if (DB::table('MPengaturanAi')->where('KodePengaturan', 'DEFAULT')->exists()) {
            return;
        }

        DB::table('MPengaturanAi')->insert([
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
        ]);
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

        if (in_array($provider, ['deepseek', 'openrouter'], true)) {
            $service = $provider === 'openrouter' ? 'openrouter' : 'deepseek';

            if ($baseUrl === '' || str_contains($baseUrl, 'api.openai.com') || ($provider === 'openrouter' && str_contains($baseUrl, 'api.deepseek.com')) || ($provider === 'deepseek' && str_contains($baseUrl, 'openrouter.ai'))) {
                $data['BaseUrl'] = config("services.{$service}.base_url");
            }

            if ($model === '' || str_starts_with($model, 'gpt-') || ($provider === 'openrouter' && str_starts_with($model, 'deepseek-')) || ($provider === 'deepseek' && str_contains($model, '/'))) {
                $data['ModelAi'] = config("services.{$service}.model");
            }

            return $data;
        }

        if ($baseUrl === '' || str_contains($baseUrl, 'api.deepseek.com') || str_contains($baseUrl, 'openrouter.ai')) {
            $data['BaseUrl'] = config('services.openai.base_url');
        }

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
            default => (string) config('services.openai.model'),
        };
    }

    private function defaultBaseUrl(string $provider): string
    {
        return match (strtolower($provider)) {
            'deepseek' => (string) config('services.deepseek.base_url'),
            'openrouter' => (string) config('services.openrouter.base_url'),
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
            ],
            'DeepSeek' => [
                'label' => 'DeepSeek',
                'summary' => 'Alternatif hemat dengan API sendiri.',
                'model' => (string) config('services.deepseek.model'),
                'base_url' => (string) config('services.deepseek.base_url'),
                'key_label' => 'DEEPSEEK_API_KEY',
            ],
            'OpenRouter' => [
                'label' => 'OpenRouter',
                'summary' => 'Router banyak model, termasuk opsi free.',
                'model' => (string) config('services.openrouter.model'),
                'base_url' => (string) config('services.openrouter.base_url'),
                'key_label' => 'OPENROUTER_API_KEY',
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
}
