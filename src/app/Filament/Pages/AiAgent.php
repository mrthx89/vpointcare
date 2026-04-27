<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiAgent extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';

    protected static string | \UnitEnum | null $navigationGroup = 'Asisten';

    protected static ?string $navigationLabel = 'AI Agent';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'AI Agent';

    protected string $view = 'filament.pages.ai-agent';

    /** @var array<string, mixed> */
    public array $pengaturan = [];

    /** @var array<string, int> */
    public array $stats = [];

    public string $apiKeyBaru = '';

    public bool $apiKeyTerisi = false;

    public function mount(): void
    {
        $this->ensureDefaultSettings();
        $this->loadPengaturan();
    }

    public function simpanPengaturan(): void
    {
        $validated = $this->validate([
            'pengaturan.AutoReplyAktif' => ['boolean'],
            'pengaturan.AutoReplyDiluarJamKerja' => ['boolean'],
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
        $data['KirimKeWaha'] = (bool) $data['KirimKeWaha'] || $data['ModeKirim'] === 'KirimWaha';
        $data['ModeKirim'] = $data['KirimKeWaha'] ? 'KirimWaha' : 'DraftLokal';
        $data['TglEdit'] = now();

        if ($this->apiKeyBaru !== '') {
            $data['ApiKeyTerenkripsi'] = Crypt::encryptString($this->apiKeyBaru);
        }

        DB::table('MPengaturanAi')
            ->where('KodePengaturan', 'DEFAULT')
            ->update($data);

        $this->apiKeyBaru = '';
        $this->loadPengaturan();

        Notification::make()
            ->title('Pengaturan AI Agent tersimpan.')
            ->success()
            ->send();
    }

    public function hapusApiKey(): void
    {
        DB::table('MPengaturanAi')
            ->where('KodePengaturan', 'DEFAULT')
            ->update([
                'ApiKeyTerenkripsi' => null,
                'TglEdit' => now(),
            ]);

        $this->loadPengaturan();

        Notification::make()
            ->title('API key AI dihapus dari database.')
            ->success()
            ->send();
    }

    private function loadPengaturan(): void
    {
        $row = DB::table('MPengaturanAi')->where('KodePengaturan', 'DEFAULT')->first();

        $this->pengaturan = [
            'AutoReplyAktif' => (bool) $row->AutoReplyAktif,
            'AutoReplyDiluarJamKerja' => (bool) $row->AutoReplyDiluarJamKerja,
            'AutoReplyJamKerjaSapaan' => (bool) $row->AutoReplyJamKerjaSapaan,
            'AutoReplyJamKerjaBerlanjut' => (bool) $row->AutoReplyJamKerjaBerlanjut,
            'JamKerjaMulai' => substr((string) $row->JamKerjaMulai, 0, 5),
            'JamKerjaSelesai' => substr((string) $row->JamKerjaSelesai, 0, 5),
            'HariKerja' => array_values(array_filter(explode(',', (string) $row->HariKerja))),
            'ZonaWaktu' => $row->ZonaWaktu ?: 'Asia/Jakarta',
            'ProviderAi' => $row->ProviderAi ?: 'OpenAI',
            'ModelAi' => $row->ModelAi ?: 'gpt-5',
            'BaseUrl' => $row->BaseUrl ?: 'https://api.openai.com/v1/responses',
            'PromptSistem' => $row->PromptSistem,
            'TemplateDiluarJamKerja' => $row->TemplateDiluarJamKerja,
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

        $this->apiKeyTerisi = filled($row->ApiKeyTerenkripsi) || filled(config('services.openai.api_key'));
        $this->stats = [
            'chat_auto' => (int) DB::table('TChatM')->where('AutoReplyAiAktif', true)->count(),
            'balasan_ai' => (int) DB::table('TChatD')->where('DihasilkanOlehAi', true)->count(),
            'permintaan_hari_ini' => (int) DB::table('TAiPermintaan')->whereDate('TglBuat', now()->toDateString())->count(),
            'penerima_notifikasi' => (int) DB::table('MPengguna')->where('NonAktif', false)->whereNotNull('NomorWhatsappInternal')->where('NomorWhatsappInternal', '<>', '')->count(),
        ];
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

    private function defaultNotificationTemplate(): string
    {
        return 'Halo {nama_user}, ada chat WhatsApp dari {nama_instansi} yang belum dibalas selama {menit_menunggu} menit. Kontak: {nama_kontak} ({nomor_whatsapp}). Pesan terakhir: {pesan_terakhir}. Silakan cek VPoint Care: {url_admin}';
    }
}
