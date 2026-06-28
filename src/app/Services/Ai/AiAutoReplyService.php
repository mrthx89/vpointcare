<?php

namespace App\Services\Ai;

use App\Support\AiSettings;
use App\Support\SchemaCache;
use App\Support\WahaChatHelper;

use App\Services\Waha\WahaSender;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiAutoReplyService
{
    public function __construct(private readonly WahaSender $wahaSender)
    {
    }

    public function testProviderConnection(object $settings, string $prompt): string
    {
        $reply = $this->generateReply($settings, $prompt);

        if (! $reply || trim($reply['text']) === '') {
            throw new RuntimeException(__('ui.ai_learning.provider_empty_answer'));
        }

        return trim($reply['text']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function handleIncomingChat(string $chatId): ?array
    {
        $settings = $this->settings();

        if (! $settings || ! (bool) $settings->AutoReplyAktif) {
            return null;
        }

        $chat = DB::table('TChat as c')
            ->leftJoin('MSesiWhatsapp as s', 's.Id', '=', 'c.IdSesiWhatsapp')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->leftJoin('MCustomer as m', 'm.Id', '=', 'c.IdCustomer')
            ->leftJoin('MGrupWhatsapp as g', 'g.Id', '=', 'c.IdGrupWhatsapp')
            ->where('c.Id', $chatId)
            ->select('c.*', 's.KodeSesi', 'i.NamaInstansi', 'm.NamaCustomer', 'g.IdGrupWaha')
            ->first();

        if (! $chat) {
            return null;
        }

        $latestIncoming = DB::table('TChatD')
            ->where('IdChat', $chatId)
            ->where('ArahPesan', 'Masuk')
            ->where('DikirimOlehCustomer', true)
            ->whereNotNull('IsiPesan')
            ->orderByDesc('TglPesan')
            ->first();

        if (! $latestIncoming) {
            return null;
        }

        $alreadyAnswered = DB::table('TChatD')
            ->where('IdChat', $chatId)
            ->where('ArahPesan', 'Keluar')
            ->where('DihasilkanOlehAi', true)
            ->where('TglPesan', '>=', $latestIncoming->TglPesan)
            ->exists();

        if ($alreadyAnswered) {
            return [
                'ok' => true,
                'skipped' => true,
                'reason' => 'Pesan terakhir sudah dijawab AI.',
            ];
        }

        $decision = $this->replyDecision($settings, $chat);

        if (! $decision['boleh']) {
            return [
                'ok' => true,
                'skipped' => true,
                'reason' => $decision['alasan'],
            ];
        }

        $requestId = (string) Str::orderedUuid();
        $prompt = $this->buildPrompt($settings, $chat, $decision['template']);
        $reply = $decision['template'];
        $responsePayload = null;
        $status = 'Selesai';
        $error = null;
        $usedAi = false;

        DB::table('TAiPermintaan')->insert([
            'Id' => $requestId,
            'JenisPermintaan' => 'Auto Reply WhatsApp',
            'ProviderAi' => $settings->ProviderAi ?: 'OpenAI',
            'ModelAi' => $settings->ModelAi ?: config('services.openai.model'),
            'IdChat' => $chatId,
            'PromptRingkas' => Str::limit($prompt, 2000, ''),
            'PromptJson' => json_encode([
                'keputusan' => $decision,
                'prompt' => $prompt,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'StatusPermintaan' => 'Diproses',
            'TglMulai' => now(),
            'TglBuat' => now(),
        ]);

        try {
            $generated = $this->generateReply($settings, $prompt);

            if ($generated) {
                $reply = $generated['text'];
                $responsePayload = $generated['payload'];
                $usedAi = true;
            }
        } catch (Throwable $exception) {
            $status = 'Gagal Fallback';
            $error = $exception->getMessage();
        }

        if (! $usedAi && ! $error) {
            $error = 'AI tidak dipanggil karena API key kosong untuk provider ' . ($settings->ProviderAi ?: '-') . ', provider tidak didukung, atau response AI tidak berisi output.';
        }

        DB::table('TAiPermintaan')->where('Id', $requestId)->update([
            'StatusPermintaan' => $status,
            'TglSelesai' => now(),
            'PesanError' => $error,
            'TglEdit' => now(),
        ]);

        $responseId = (string) Str::orderedUuid();
        DB::table('TAiRespon')->insert([
            'Id' => $responseId,
            'IdAiPermintaan' => $requestId,
            'JenisRespon' => $decision['mode'],
            'ResponRingkas' => $reply,
            'ResponJson' => json_encode($responsePayload ?? [
                'fallback' => true,
                'reason' => $error,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'TglBuat' => now(),
        ]);

        $delivery = $this->storeReply($settings, $chat, $reply, $responseId, $decision['mode']);

        DB::table('TChat')->where('Id', $chatId)->update([
            'AiSudahMenyapa' => $decision['mode'] === 'Sapaan Jam Kerja' ? true : (bool) $chat->AiSudahMenyapa,
            'TglAutoReplyAiTerakhir' => now(),
            'TglDibalasTerakhir' => now(),
            'TglChatTerakhir' => now(),
            'JumlahPesanBelumDibaca' => 0,
            'TglEdit' => now(),
        ]);

        return [
            'ok' => true,
            'mode' => $decision['mode'],
            'delivery' => $delivery,
            'id_ai_respon' => $responseId,
        ];
    }

    private function settings(): ?object
    {
        return AiSettings::get();
    }

    public function sendClosingMessage(string $chatId): void
    {
        $settings = $this->settings();
        
        if (! $settings) {
            return;
        }

        $chat = DB::table('TChat as c')
            ->leftJoin('MSesiWhatsapp as s', 's.Id', '=', 'c.IdSesiWhatsapp')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->leftJoin('MCustomer as m', 'm.Id', '=', 'c.IdCustomer')
            ->leftJoin('MGrupWhatsapp as g', 'g.Id', '=', 'c.IdGrupWhatsapp')
            ->where('c.Id', $chatId)
            ->select('c.*', 's.KodeSesi', 'i.NamaInstansi', 'm.NamaCustomer', 'g.IdGrupWaha')
            ->first();

        if (! $chat) {
            return;
        }

        $prompt = $this->buildPrompt($settings, $chat, 'Tutup percakapan ini dengan sopan dan profesional. Ucapkan terima kasih karena telah menghubungi VPoint Care, dan sampaikan bahwa sesi percakapan ini telah ditutup. Tanyakan apakah ada hal lain yang bisa dibantu untuk ke depannya (meskipun sesi sudah ditutup). Jangan terlalu panjang.');
        $requestId = (string) Str::orderedUuid();
        $reply = 'Terima kasih telah menghubungi VPoint Care. Sesi percakapan ini telah ditutup.';

        DB::table('TAiPermintaan')->insert([
            'Id' => $requestId,
            'JenisPermintaan' => 'Tutup Chat',
            'ProviderAi' => $settings->ProviderAi ?: 'OpenAI',
            'ModelAi' => $settings->ModelAi ?: config('services.openai.model'),
            'IdChat' => $chatId,
            'PromptRingkas' => Str::limit($prompt, 2000, ''),
            'PromptJson' => json_encode(['prompt' => $prompt], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'StatusPermintaan' => 'Diproses',
            'TglMulai' => now(),
            'TglBuat' => now(),
        ]);

        $error = null;
        try {
            $generated = $this->generateReply($settings, $prompt);
            if ($generated) {
                $reply = $generated['text'];
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        DB::table('TAiPermintaan')->where('Id', $requestId)->update([
            'StatusPermintaan' => $error ? 'Gagal Fallback' : 'Selesai',
            'TglSelesai' => now(),
            'PesanError' => $error,
            'TglEdit' => now(),
        ]);

        $responseId = (string) Str::orderedUuid();
        DB::table('TAiRespon')->insert([
            'Id' => $responseId,
            'IdAiPermintaan' => $requestId,
            'JenisRespon' => 'Tutup Chat',
            'ResponRingkas' => $reply,
            'ResponJson' => json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'TglBuat' => now(),
        ]);

        $this->storeReply($settings, $chat, $reply, $responseId, 'Tutup Chat');
    }

    /**
     * @return array{boleh: bool, alasan: string, mode: string, template: string}
     */
    private function replyDecision(object $settings, object $chat): array
    {
        $holiday = $this->activeHoliday($settings);

        if ($holiday && (bool) ($settings->AutoReplyHariLibur ?? true)) {
            return [
                'boleh' => true,
                'alasan' => 'Hari libur: ' . $holiday['name'] . '.',
                'mode' => 'Hari Libur',
                'template' => $this->formatHolidayTemplate($settings, $holiday),
            ];
        }

        $outsideWorkingHour = ! $this->insideWorkingHour($settings);

        if ($outsideWorkingHour && (bool) $settings->AutoReplyDiluarJamKerja) {
            return [
                'boleh' => true,
                'alasan' => 'Di luar jam kerja.',
                'mode' => 'Luar Jam Kerja',
                'template' => $settings->TemplateDiluarJamKerja ?: $this->defaultOutsideTemplate(),
            ];
        }

        if ((bool) $chat->AutoReplyAiAktif || (bool) $settings->AutoReplyJamKerjaBerlanjut) {
            return [
                'boleh' => true,
                'alasan' => 'Auto reply sesi aktif.',
                'mode' => 'Berlanjut',
                'template' => $settings->TemplateFallback ?: $this->defaultFallbackTemplate(),
            ];
        }

        if ((bool) $settings->AutoReplyJamKerjaSapaan && ! (bool) $chat->AiSudahMenyapa) {
            return [
                'boleh' => true,
                'alasan' => 'Sapaan awal jam kerja.',
                'mode' => 'Sapaan Jam Kerja',
                'template' => $settings->TemplateJamKerjaSapaan ?: $this->defaultGreetingTemplate(),
            ];
        }

        return [
            'boleh' => false,
            'alasan' => 'Jam kerja aktif dan sesi tidak diset auto reply berlanjut.',
            'mode' => 'Skip',
            'template' => '',
        ];
    }

    /**
     * @return array{name: string, date: Carbon, next_working_date: ?Carbon}|null
     */
    private function activeHoliday(object $settings): ?array
    {
        $timezone = $settings->ZonaWaktu ?: config('app.timezone', 'Asia/Jakarta');
        $today = Carbon::now($timezone)->startOfDay();
        $holiday = $this->holidayForDate($today);

        if (! $holiday) {
            return null;
        }

        return [
            'name' => (string) $holiday->NamaHariLibur,
            'date' => $today,
            'next_working_date' => $this->nextWorkingDate($settings, $today),
        ];
    }

    private function holidayForDate(Carbon $date): ?object
    {
        if (! SchemaCache::hasTable('MHariLibur')) {
            return null;
        }

        return DB::table('MHariLibur')
            ->where('NonAktif', false)
            ->where(function ($query) use ($date): void {
                $query
                    ->whereDate('TanggalLibur', $date->toDateString())
                    ->orWhere(function ($query) use ($date): void {
                        $query
                            ->where('BerlakuTahunan', true)
                            ->whereRaw('MONTH(TanggalLibur) = ?', [$date->month])
                            ->whereRaw('DAY(TanggalLibur) = ?', [$date->day]);
                    });
            })
            ->orderByDesc('BerlakuTahunan')
            ->orderBy('NamaHariLibur')
            ->first();
    }

    private function nextWorkingDate(object $settings, Carbon $fromDate): ?Carbon
    {
        $workdays = array_map('intval', explode(',', (string) $settings->HariKerja));
        $date = $fromDate->copy()->addDay()->startOfDay();

        for ($attempt = 0; $attempt < 60; $attempt++, $date->addDay()) {
            if (! in_array($date->dayOfWeekIso, $workdays, true)) {
                continue;
            }

            if ($this->holidayForDate($date)) {
                continue;
            }

            return $date->copy();
        }

        return null;
    }

    /**
     * @param  array{name: string, date: Carbon, next_working_date: ?Carbon}  $holiday
     */
    private function formatHolidayTemplate(object $settings, array $holiday): string
    {
        $template = ($settings->TemplateHariLibur ?? null) ?: $this->defaultHolidayTemplate();
        $nextWorkingDate = $holiday['next_working_date']
            ? $this->formatIndonesianDate($holiday['next_working_date'])
            : 'hari kerja berikutnya';

        return strtr($template, [
            '{nama_hari_libur}' => $holiday['name'],
            '{tanggal_libur}' => $this->formatIndonesianDate($holiday['date']),
            '{tanggal_masuk_kerja}' => $nextWorkingDate,
        ]);
    }

    private function insideWorkingHour(object $settings): bool
    {
        $timezone = $settings->ZonaWaktu ?: config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($timezone);
        $workdays = array_map('intval', explode(',', (string) $settings->HariKerja));

        if (! in_array($now->dayOfWeekIso, $workdays, true)) {
            return false;
        }

        $start = Carbon::parse($now->toDateString() . ' ' . (string) $settings->JamKerjaMulai, $timezone);
        $end = Carbon::parse($now->toDateString() . ' ' . (string) $settings->JamKerjaSelesai, $timezone);

        return $now->betweenIncluded($start, $end);
    }

    private function formatIndonesianDate(Carbon $date): string
    {
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return sprintf(
            '%s, %d %s %d',
            $days[$date->dayOfWeekIso],
            $date->day,
            $months[$date->month],
            $date->year
        );
    }

    private function buildPrompt(object $settings, object $chat, string $template): string
    {
        $limit = max(1, min((int) $settings->BatasRiwayatPesan, 20));
        $chatMessages = DB::table('TChatD')
            ->where('IdChat', $chat->Id)
            ->orderByDesc('TglPesan')
            ->limit($limit)
            ->get()
            ->reverse();

        $messages = $chatMessages
            ->map(function (object $row): string {
                $speaker = $row->ArahPesan === 'Keluar'
                    ? ((bool) ($row->DihasilkanOlehAi ?? false) ? 'AI Agent' : 'Customer Service')
                    : ($row->PengirimNamaKontak ?: $row->PengirimNomorWhatsapp ?: 'Customer');

                return $speaker . ': ' . ($row->IsiPesan ?: '[pesan non-teks]');
            })
            ->implode("\n");

        $latestCustomerMessage = (string) ($chatMessages
            ->where('ArahPesan', 'Masuk')
            ->where('IsiPesan', '<>', null)
            ->last()
            ->IsiPesan ?? '');

        $customer = $chat->NamaInstansi ?: $chat->NamaCustomer ?: 'Belum dipetakan';
        $knowledge = $this->relevantKnowledge(
            $latestCustomerMessage . ' ' . $messages . ' ' . $customer,
            (string) ($chat->ModeKnowledgeAi ?? 'Ringan'),
            (int) ($chat->BatasKnowledgeAi ?? 0)
        );

        return trim(implode("\n\n", array_filter([
            $settings->PromptSistem ?: null,
            'Konteks customer: ' . $customer,
            'Jenis chat: ' . $chat->JenisChat,
            'Instruksi mode: gunakan template berikut hanya sebagai arah balasan, bukan kalimat yang harus diulang persis.',
            'Template: ' . $template,
            $knowledge ? 'Pengetahuan internal yang boleh dipakai:' . "\n" . $knowledge : null,
            'Riwayat chat:',
            $messages,
            'Buat satu balasan WhatsApp yang halus, ringkas, natural, dan siap dikirim. AI boleh mengimprovisasi susunan kalimat agar tidak kaku atau berulang, tetapi fakta, prosedur, harga, jadwal, dan janji layanan harus mengikuti pengetahuan internal atau riwayat chat. Jika informasi tidak tersedia, minta detail tambahan atau arahkan ke customer service tanpa mengarang.',
        ])));
    }

    private function relevantKnowledge(string $context, string $mode = 'Ringan', int $customLimit = 0): string
    {
        $mode = in_array($mode, ['Ringan', 'AllKnowledge', 'Nonaktif'], true) ? $mode : 'Ringan';

        if ($mode === 'Nonaktif') {
            return '';
        }

        $tokens = $this->knowledgeTokens($context);
        $maxItems = $mode === 'AllKnowledge' ? max(1, min($customLimit ?: 20, 50)) : max(1, min($customLimit ?: 5, 10));
        $maxTotalChars = $mode === 'AllKnowledge' ? 12000 : 3500;
        $maxItemChars = $mode === 'AllKnowledge' ? 1200 : 900;

        $query = DB::table('MPengetahuan')
            ->where('NonAktif', false)
            ->select(
                'Id',
                'JudulPengetahuan',
                'IsiPengetahuan',
                'Tag',
                SchemaCache::hasColumn('MPengetahuan', 'SearchKeywords') ? 'SearchKeywords' : DB::raw('NULL as SearchKeywords'),
                SchemaCache::hasColumn('MPengetahuan', 'PrioritasAi') ? 'PrioritasAi' : DB::raw('0 as PrioritasAi')
            );

        if ($mode !== 'AllKnowledge' && $tokens->isNotEmpty()) {
            $query->where(function ($where) use ($tokens): void {
                foreach ($tokens->take(10) as $token) {
                    $like = '%' . str_replace(['%', '_', '['], ['[%]', '[_]', '[[]'], $token) . '%';
                    $where->orWhere('JudulPengetahuan', 'like', $like)
                        ->orWhere('Tag', 'like', $like)
                        ->orWhere('IsiPengetahuan', 'like', $like);

                    if (SchemaCache::hasColumn('MPengetahuan', 'SearchKeywords')) {
                        $where->orWhere('SearchKeywords', 'like', $like);
                    }
                }
            });
        }

        $rows = $query
            ->orderByDesc(SchemaCache::hasColumn('MPengetahuan', 'PrioritasAi') ? 'PrioritasAi' : 'JudulPengetahuan')
            ->orderBy('JudulPengetahuan')
            ->limit($mode === 'AllKnowledge' ? $maxItems : 50)
            ->get();

        if ($rows->isEmpty()) {
            return '';
        }

        $scored = $rows
            ->map(function (object $row) use ($tokens, $mode): array {
                $title = Str::lower((string) $row->JudulPengetahuan);
                $tag = Str::lower((string) $row->Tag);
                $keywords = Str::lower((string) ($row->SearchKeywords ?? ''));
                $content = Str::lower((string) $row->IsiPengetahuan);
                $score = (int) ($row->PrioritasAi ?? 0);

                if ($mode === 'AllKnowledge') {
                    $score += 1;
                }

                foreach ($tokens as $token) {
                    $score += str_contains($title, $token) ? 5 : 0;
                    $score += str_contains($tag, $token) ? 4 : 0;
                    $score += str_contains($keywords, $token) ? 3 : 0;
                    $score += str_contains($content, $token) ? 1 : 0;
                }

                return [
                    'id' => (string) $row->Id,
                    'score' => $score,
                    'title' => (string) $row->JudulPengetahuan,
                    'content' => (string) $row->IsiPengetahuan,
                ];
            })
            ->filter(fn (array $row): bool => $mode === 'AllKnowledge' || $row['score'] >= 4)
            ->sortByDesc('score')
            ->take($maxItems)
            ->values();

        if ($scored->isEmpty()) {
            return '';
        }

        $used = [];
        $total = 0;
        $lines = [];

        foreach ($scored as $row) {
            $content = Str::limit($row['content'], $maxItemChars, '');
            $line = '- ' . $row['title'] . ': ' . $content;

            if ($total + mb_strlen($line) > $maxTotalChars) {
                break;
            }

            $lines[] = $line;
            $used[] = $row['id'];
            $total += mb_strlen($line);
        }

        if ($used && SchemaCache::hasColumn('MPengetahuan', 'JumlahDipakaiAi')) {
            DB::table('MPengetahuan')->whereIn('Id', $used)->update([
                'TerakhirDipakaiAi' => now(),
                'JumlahDipakaiAi' => DB::raw('JumlahDipakaiAi + 1'),
            ]);
        }

        return implode("\n", $lines);
    }

    private function knowledgeTokens(string $context): \Illuminate\Support\Collection
    {
        return collect(preg_split('/[\s,.;:!?()\[\]{}"\'\/\\\-]+/u', Str::lower($context)) ?: [])
            ->map(fn (string $token): string => trim($token))
            ->filter(fn (string $token): bool => mb_strlen($token) >= 4)
            ->reject(fn (string $token): bool => in_array($token, [
                'yang', 'dari', 'untuk', 'dengan', 'atau', 'kami', 'saya', 'anda', 'bapak', 'ibu',
                'halo', 'terima', 'kasih', 'pesan', 'customer', 'mohon', 'tolong', 'sudah', 'belum',
            ], true))
            ->unique()
            ->values();
    }
    /**
     * @return array{text: string, payload: array<string, mixed>}|null
     */
    private function generateReply(object $settings, string $prompt): ?array
    {
        $provider = strtolower((string) $settings->ProviderAi);
        $apiKey = $this->apiKey($settings, $provider);

        if (! $apiKey) {
            return null;
        }

        if ($provider === 'deepseek') {
            return $this->generateChatCompletionReply($settings, $prompt, $apiKey, 'deepseek');
        }

        if ($provider === 'openrouter') {
            return $this->generateChatCompletionReply($settings, $prompt, $apiKey, 'openrouter');
        }

        if (in_array($provider, ['9router', 'ninerouter'], true)) {
            return $this->generateChatCompletionReply($settings, $prompt, $apiKey, 'ninerouter');
        }

        if ($provider === 'openai') {
            return $this->generateOpenAiReply($settings, $prompt, $apiKey);
        }

        return null;
    }

    /**
     * @return array{text: string, payload: array<string, mixed>}|null
     */
    private function generateOpenAiReply(object $settings, string $prompt, string $apiKey): ?array
    {
        $baseUrl = $settings->BaseUrl ?: config('services.openai.base_url');
        $model = $settings->ModelAi ?: config('services.openai.model');

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post($baseUrl, [
                'model' => $model,
                'instructions' => $settings->PromptSistem ?: null,
                'input' => $prompt,
                'store' => true,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI API gagal: HTTP ' . $response->status() . ' - ' . $response->body());
        }

        $payload = $response->json();
        $text = $this->extractOutputText($payload);

        if (! $text) {
            return null;
        }

        return [
            'text' => $text,
            'payload' => $payload,
        ];
    }

    /**
     * @return array{text: string, payload: array<string, mixed>}|null
     */
    private function generateChatCompletionReply(object $settings, string $prompt, string $apiKey, string $provider): ?array
    {
        $baseUrl = $this->chatCompletionEndpoint((string) ($settings->BaseUrl ?: config("services.{$provider}.base_url")));
        $model = $settings->ModelAi ?: config("services.{$provider}.model");
        $request = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(30);

        if (in_array($provider, ['openrouter', 'ninerouter'], true)) {
            $request = $request->withHeaders(array_filter([
                'HTTP-Referer' => config("services.{$provider}.site_url"),
                'X-Title' => config("services.{$provider}.site_name"),
            ]));
        }

        $response = $request->post($baseUrl, [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $settings->PromptSistem ?: 'Anda adalah AI Agent customer service yang menjawab singkat, sopan, dan jelas.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'stream' => false,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(ucfirst($provider) . ' API gagal: HTTP ' . $response->status() . ' - ' . $response->body());
        }

        $payload = $response->json();
        $text = trim((string) Arr::get($payload, 'choices.0.message.content', ''));

        if ($text === '') {
            return null;
        }

        return [
            'text' => $text,
            'payload' => $payload,
        ];
    }

    private function chatCompletionEndpoint(string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        if (str_ends_with($baseUrl, '/chat/completions')) {
            return $baseUrl;
        }

        return $baseUrl . '/chat/completions';
    }

    private function apiKey(object $settings, string $provider): ?string
    {
        $encryptedApiKey = $this->providerDbApiKey($settings, $provider);

        if ($encryptedApiKey) {
            try {
                return Crypt::decryptString($encryptedApiKey);
            } catch (Throwable) {
                return $this->providerApiKey($provider);
            }
        }

        return $this->providerApiKey($provider);
    }

    private function providerApiKey(string $provider): ?string
    {
        return match ($provider) {
            'deepseek' => config('services.deepseek.api_key'),
            'openrouter' => config('services.openrouter.api_key'),
            '9router', 'ninerouter' => config('services.ninerouter.api_key'),
            default => config('services.openai.api_key'),
        };
    }

    private function providerDbApiKey(object $settings, string $provider): ?string
    {
        $column = match ($provider) {
            'deepseek' => 'DeepSeekApiKeyTerenkripsi',
            'openrouter' => 'OpenRouterApiKeyTerenkripsi',
            '9router', 'ninerouter' => 'NineRouterApiKeyTerenkripsi',
            default => 'OpenAiApiKeyTerenkripsi',
        };

        $apiKey = $settings->{$column} ?? null;

        if ($apiKey) {
            return $apiKey;
        }

        return $provider === 'openai'
            ? ($settings->ApiKeyTerenkripsi ?? null)
            : null;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractOutputText(?array $payload): ?string
    {
        $outputText = trim((string) Arr::get($payload, 'output_text', ''));

        if ($outputText !== '') {
            return $outputText;
        }

        foreach ((array) Arr::get($payload, 'output', []) as $output) {
            foreach ((array) Arr::get($output, 'content', []) as $content) {
                $text = trim((string) (Arr::get($content, 'text') ?? Arr::get($content, 'content')));

                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function storeReply(object $settings, object $chat, string $reply, string $responseId, string $mode): array
    {
        $status = 'Draft Auto Reply AI';
        $sentAt = null;
        $error = null;

        if ((bool) $settings->KirimKeWaha) {
            $sent = $this->sendToWaha($chat, $reply);
            $status = $sent['ok'] ? 'Terkirim WAHA' : 'Gagal WAHA';
            $sentAt = $sent['ok'] ? now() : null;
            $error = $sent['error'] ?? null;
        }

        DB::table('TChatD')->insert([
            'Id' => (string) Str::orderedUuid(),
            'IdChat' => $chat->Id,
            'IdAiRespon' => $responseId,
            'ArahPesan' => 'Keluar',
            'JenisPesan' => 'Teks',
            'IsiPesan' => $reply,
            'DikirimOlehCustomer' => false,
            'DihasilkanOlehAi' => true,
            'TglPesan' => now(),
            'TglDikirim' => $sentAt,
            'StatusKirim' => $status,
            'PesanError' => $error,
            'TglBuat' => now(),
        ]);

        return [
            'mode_kirim' => $settings->KirimKeWaha ? 'WAHA' : 'DraftLokal',
            'status' => $status,
            'auto_reply_mode' => $mode,
            'error' => $error,
        ];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    private function sendToWaha(object $chat, string $reply): array
    {
        $chatId = $this->wahaChatId($chat);

        return $this->wahaSender->sendText(
            $chat->KodeSesi ?: 'default',
            $chatId,
            $reply,
            'WAHA_SEND_TEXT'
        );
    }

    private function wahaChatId(object $chat): string
    {
        if ($chat->JenisChat === 'Grup' && $chat->IdGrupWaha) {
            return $chat->IdGrupWaha;
        }

        $latestIncomingChatId = WahaChatHelper::latestIncomingWahaChatId((string) $chat->Id);

        if ($latestIncomingChatId) {
            return $latestIncomingChatId;
        }

        return WahaChatHelper::normalizeChatId((string) $chat->NomorWhatsapp);
    }

    private function latestIncomingWahaChatId(string $chatId): ?string
    {
        $payloadJson = DB::table('TChatD')
            ->where('IdChat', $chatId)
            ->where('ArahPesan', 'Masuk')
            ->whereNotNull('PayloadJson')
            ->orderByDesc('TglPesan')
            ->value('PayloadJson');

        if (! $payloadJson) {
            return null;
        }

        $payload = json_decode((string) $payloadJson, true);

        if (! is_array($payload)) {
            return null;
        }

        foreach ([
            'chatId',
            'from',
            'from.id',
            '_data.id.remote',
            '_data.Info.Chat',
            'key.remoteJid',
        ] as $key) {
            $value = Arr::get($payload, $key);

            if (is_string($value) && $value !== '') {
                return $this->normalizeWahaChatId($value);
            }
        }

        return null;
    }

    private function normalizeWahaChatId(string $chatIdOrNumber): string
    {
        if (str_contains($chatIdOrNumber, '@')) {
            return str_ends_with($chatIdOrNumber, '@s.whatsapp.net')
                ? str_replace('@s.whatsapp.net', '@c.us', $chatIdOrNumber)
                : $chatIdOrNumber;
        }

        $number = preg_replace('/[^0-9]/', '', $chatIdOrNumber) ?: $chatIdOrNumber;

        return $number . '@c.us';
    }

    private function defaultOutsideTemplate(): string
    {
        return 'Terima kasih sudah menghubungi VPoint Care. Saat ini kami berada di luar jam operasional. Pesan Bapak/Ibu sudah kami terima dan akan kami tindak lanjuti pada jam kerja berikutnya.';
    }

    private function defaultHolidayTemplate(): string
    {
        return 'Terima kasih sudah menghubungi VPoint Care. Hari ini kami sedang libur ({nama_hari_libur}). Pesan Bapak/Ibu tetap kami terima dan akan kami teruskan ke tim customer service. Silakan sampaikan detail kendalanya agar tim kami bisa menindaklanjuti pada hari kerja berikutnya, {tanggal_masuk_kerja}. Mohon maaf atas ketidaknyamanannya.';
    }

    private function defaultGreetingTemplate(): string
    {
        return 'Halo, terima kasih sudah menghubungi VPoint Care. Saya bantu catat terlebih dahulu ya. Silakan jelaskan kendala yang sedang dialami, nanti tim customer service kami akan melanjutkan penanganannya.';
    }

    private function defaultFallbackTemplate(): string
    {
        return 'Terima kasih informasinya. Pesan sudah kami terima dan akan kami teruskan ke tim terkait untuk ditindaklanjuti.';
    }
}

