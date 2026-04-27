<?php

namespace App\Services\Ai;

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
    /**
     * @return array<string, mixed>|null
     */
    public function handleIncomingChat(string $chatId): ?array
    {
        $settings = $this->settings();

        if (! $settings || ! (bool) $settings->AutoReplyAktif) {
            return null;
        }

        $chat = DB::table('TChatM as c')
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
            ->where('IdChatM', $chatId)
            ->where('ArahPesan', 'Masuk')
            ->where('DikirimOlehCustomer', true)
            ->whereNotNull('IsiPesan')
            ->orderByDesc('TglPesan')
            ->first();

        if (! $latestIncoming) {
            return null;
        }

        $alreadyAnswered = DB::table('TChatD')
            ->where('IdChatM', $chatId)
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

        DB::table('TAiPermintaan')->insert([
            'Id' => $requestId,
            'JenisPermintaan' => 'Auto Reply WhatsApp',
            'ProviderAi' => $settings->ProviderAi ?: 'OpenAI',
            'ModelAi' => $settings->ModelAi ?: config('services.openai.model'),
            'IdChatM' => $chatId,
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
            }
        } catch (Throwable $exception) {
            $status = 'Gagal Fallback';
            $error = $exception->getMessage();
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
            'ResponJson' => json_encode($responsePayload ?? ['fallback' => true], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'TglBuat' => now(),
        ]);

        $delivery = $this->storeReply($settings, $chat, $reply, $responseId, $decision['mode']);

        DB::table('TChatM')->where('Id', $chatId)->update([
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
        return DB::table('MPengaturanAi')
            ->where('KodePengaturan', 'DEFAULT')
            ->where('NonAktif', false)
            ->first();
    }

    /**
     * @return array{boleh: bool, alasan: string, mode: string, template: string}
     */
    private function replyDecision(object $settings, object $chat): array
    {
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

    private function buildPrompt(object $settings, object $chat, string $template): string
    {
        $limit = max(1, min((int) $settings->BatasRiwayatPesan, 20));
        $messages = DB::table('TChatD')
            ->where('IdChatM', $chat->Id)
            ->orderByDesc('TglPesan')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(function (object $row): string {
                $speaker = $row->ArahPesan === 'Keluar'
                    ? ((bool) ($row->DihasilkanOlehAi ?? false) ? 'AI Agent' : 'Customer Service')
                    : ($row->PengirimNamaKontak ?: $row->PengirimNomorWhatsapp ?: 'Customer');

                return $speaker . ': ' . ($row->IsiPesan ?: '[pesan non-teks]');
            })
            ->implode("\n");

        $customer = $chat->NamaInstansi ?: $chat->NamaCustomer ?: 'Belum dipetakan';

        return trim(implode("\n\n", array_filter([
            $settings->PromptSistem ?: null,
            'Konteks customer: ' . $customer,
            'Jenis chat: ' . $chat->JenisChat,
            'Instruksi mode: gunakan template berikut sebagai arah balasan, lalu sesuaikan dengan isi chat jika memang relevan. Jangan menjawab teknis yang belum pasti.',
            'Template: ' . $template,
            'Riwayat chat:',
            $messages,
            'Buat satu balasan WhatsApp yang halus, ringkas, dan siap dikirim.',
        ])));
    }

    /**
     * @return array{text: string, payload: array<string, mixed>}|null
     */
    private function generateReply(object $settings, string $prompt): ?array
    {
        $apiKey = $this->apiKey($settings);

        if (! $apiKey || strtolower((string) $settings->ProviderAi) !== 'openai') {
            return null;
        }

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

    private function apiKey(object $settings): ?string
    {
        if ($settings->ApiKeyTerenkripsi) {
            try {
                return Crypt::decryptString($settings->ApiKeyTerenkripsi);
            } catch (Throwable) {
                return config('services.openai.api_key');
            }
        }

        return config('services.openai.api_key');
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
            'IdChatM' => $chat->Id,
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
        $baseUrl = rtrim((string) config('services.waha.base_url'), '/');
        $path = '/' . ltrim((string) config('services.waha.send_text_path', '/api/sendText'), '/');
        $url = $baseUrl . $path;
        $chatId = $this->wahaChatId($chat);
        $payload = [
            'session' => $chat->KodeSesi ?: 'default',
            'chatId' => $chatId,
            'text' => $reply,
        ];
        $logId = (string) Str::orderedUuid();

        DB::table('TLogIntegrasi')->insert([
            'Id' => $logId,
            'KodeIntegrasi' => 'WAHA_SEND_TEXT',
            'UrlEndpoint' => $url,
            'MetodeHttp' => 'POST',
            'RequestJson' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'TglRequest' => now(),
            'TglBuat' => now(),
        ]);

        try {
            $request = Http::acceptJson()->asJson()->timeout(20);

            if (config('services.waha.api_key')) {
                $request = $request->withHeader('X-Api-Key', (string) config('services.waha.api_key'));
            }

            $response = $request->post($url, $payload);

            DB::table('TLogIntegrasi')->where('Id', $logId)->update([
                'ResponseJson' => $response->body(),
                'StatusHttp' => $response->status(),
                'Berhasil' => $response->successful(),
                'PesanError' => $response->successful() ? null : $response->body(),
                'TglResponse' => now(),
                'TglEdit' => now(),
            ]);

            return $response->successful()
                ? ['ok' => true]
                : ['ok' => false, 'error' => $response->body()];
        } catch (Throwable $exception) {
            DB::table('TLogIntegrasi')->where('Id', $logId)->update([
                'Berhasil' => false,
                'PesanError' => $exception->getMessage(),
                'TglResponse' => now(),
                'TglEdit' => now(),
            ]);

            return ['ok' => false, 'error' => $exception->getMessage()];
        }
    }

    private function wahaChatId(object $chat): string
    {
        if ($chat->JenisChat === 'Grup' && $chat->IdGrupWaha) {
            return $chat->IdGrupWaha;
        }

        $number = preg_replace('/[^0-9]/', '', (string) $chat->NomorWhatsapp) ?: (string) $chat->NomorWhatsapp;

        return str_contains($number, '@') ? $number : $number . '@c.us';
    }

    private function defaultOutsideTemplate(): string
    {
        return 'Terima kasih sudah menghubungi VPoint Care. Saat ini kami berada di luar jam operasional. Pesan Bapak/Ibu sudah kami terima dan akan kami tindak lanjuti pada jam kerja berikutnya.';
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
