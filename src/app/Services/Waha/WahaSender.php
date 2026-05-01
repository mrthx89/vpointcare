<?php

namespace App\Services\Waha;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class WahaSender
{
    /**
     * @return array{ok: bool, status?: int, body?: string, error?: string}
     */
    public function sendText(string $session, string $chatIdOrNumber, string $text, string $kodeIntegrasi = 'WAHA_SEND_TEXT'): array
    {
        $payload = [
            'session' => $session,
            'chatId' => $this->normalizeChatId($chatIdOrNumber),
            'text' => $text,
        ];

        return $this->postJson((string) config('services.waha.send_text_path', '/api/sendText'), $payload, $kodeIntegrasi);
    }

    /**
     * @return array{ok: bool, status?: int, body?: string, error?: string}
     */
    public function sendMedia(
        string $session,
        string $chatIdOrNumber,
        string $base64Data,
        string $mimeType,
        string $fileName,
        ?string $caption = null,
        string $kodeIntegrasi = 'WAHA_SEND_MEDIA'
    ): array {
        $path = match (true) {
            str_starts_with($mimeType, 'image/') => '/api/sendImage',
            str_starts_with($mimeType, 'video/') => '/api/sendVideo',
            default => '/api/sendFile',
        };

        $payload = [
            'session' => $session,
            'chatId' => $this->normalizeChatId($chatIdOrNumber),
            'file' => [
                'mimetype' => $mimeType,
                'filename' => $fileName,
                'data' => $base64Data,
            ],
        ];

        if ($caption !== null && trim($caption) !== '') {
            $payload['caption'] = $caption;
        }

        if (str_starts_with($mimeType, 'video/')) {
            $payload['convert'] = false;
            $payload['asNote'] = false;
        }

        return $this->postJson($path, $payload, $kodeIntegrasi);
    }

    /**
     * @return array{ok: bool, url?: ?string, status?: int, body?: string, error?: string}
     */
    public function getContactProfilePictureUrl(string $session, string $contactId, bool $refresh = false): array
    {
        $response = $this->getJson('/api/contacts/profile-picture', [
            'contactId' => $this->normalizeContactId($contactId),
            'session' => $session,
            'refresh' => $refresh ? 'true' : 'false',
        ], 'WAHA_CONTACT_PROFILE_PICTURE');

        if (! ($response['ok'] ?? false)) {
            return $response;
        }

        $payload = json_decode((string) ($response['body'] ?? ''), true);
        $url = is_array($payload)
            ? (Arr::get($payload, 'profilePictureURL') ?? Arr::get($payload, 'url'))
            : null;

        return array_merge($response, [
            'url' => is_string($url) && trim($url) !== '' ? trim($url) : null,
        ]);
    }

    /**
     * @return array{ok: bool, phone?: ?string, pn?: ?string, status?: int, body?: string, error?: string}
     */
    public function getPhoneNumberByLid(string $session, string $lid): array
    {
        $response = $this->getJson('/api/' . rawurlencode($session) . '/lids/' . rawurlencode($this->normalizeContactId($lid)), [], 'WAHA_LID_TO_PHONE');

        if (! ($response['ok'] ?? false)) {
            return $response;
        }

        $payload = json_decode((string) ($response['body'] ?? ''), true);
        $pn = is_array($payload) ? Arr::get($payload, 'pn') : null;
        $phone = is_string($pn) ? $this->phoneNumberFromContactId($pn) : null;

        return array_merge($response, [
            'phone' => $phone,
            'pn' => is_string($pn) ? $pn : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, status?: int, body?: string, error?: string}
     */
    private function postJson(string $path, array $payload, string $kodeIntegrasi): array
    {
        $baseUrl = rtrim((string) config('services.waha.base_url'), '/');
        $url = $baseUrl.'/'.ltrim($path, '/');
        $logId = (string) Str::orderedUuid();

        DB::table('TLogIntegrasi')->insert([
            'Id' => $logId,
            'KodeIntegrasi' => $kodeIntegrasi,
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

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body(),
                'error' => $response->successful() ? null : $response->body(),
            ];
        } catch (Throwable $exception) {
            DB::table('TLogIntegrasi')->where('Id', $logId)->update([
                'Berhasil' => false,
                'PesanError' => $exception->getMessage(),
                'TglResponse' => now(),
                'TglEdit' => now(),
            ]);

            return [
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{ok: bool, status?: int, body?: string, error?: string}
     */
    private function getJson(string $path, array $query, string $kodeIntegrasi): array
    {
        $baseUrl = rtrim((string) config('services.waha.base_url'), '/');
        $url = $baseUrl.'/'.ltrim($path, '/');
        $logId = (string) Str::orderedUuid();

        DB::table('TLogIntegrasi')->insert([
            'Id' => $logId,
            'KodeIntegrasi' => $kodeIntegrasi,
            'UrlEndpoint' => $url,
            'MetodeHttp' => 'GET',
            'RequestJson' => json_encode($query, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'TglRequest' => now(),
            'TglBuat' => now(),
        ]);

        try {
            $request = Http::acceptJson()->timeout(8);

            if (config('services.waha.api_key')) {
                $request = $request->withHeader('X-Api-Key', (string) config('services.waha.api_key'));
            }

            $response = $request->get($url, $query);

            DB::table('TLogIntegrasi')->where('Id', $logId)->update([
                'ResponseJson' => $response->body(),
                'StatusHttp' => $response->status(),
                'Berhasil' => $response->successful(),
                'PesanError' => $response->successful() ? null : $response->body(),
                'TglResponse' => now(),
                'TglEdit' => now(),
            ]);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body(),
                'error' => $response->successful() ? null : $response->body(),
            ];
        } catch (Throwable $exception) {
            DB::table('TLogIntegrasi')->where('Id', $logId)->update([
                'Berhasil' => false,
                'PesanError' => $exception->getMessage(),
                'TglResponse' => now(),
                'TglEdit' => now(),
            ]);

            return [
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function normalizeChatId(string $chatIdOrNumber): string
    {
        if (str_contains($chatIdOrNumber, '@')) {
            return str_ends_with($chatIdOrNumber, '@s.whatsapp.net')
                ? str_replace('@s.whatsapp.net', '@c.us', $chatIdOrNumber)
                : $chatIdOrNumber;
        }

        $number = preg_replace('/[^0-9]/', '', $chatIdOrNumber) ?: $chatIdOrNumber;

        return $number.'@c.us';
    }

    private function normalizeContactId(string $contactId): string
    {
        $contactId = trim($contactId);

        if (str_contains($contactId, '@')) {
            return str_ends_with($contactId, '@s.whatsapp.net')
                ? str_replace('@s.whatsapp.net', '@c.us', $contactId)
                : $contactId;
        }

        $number = preg_replace('/[^0-9]/', '', $contactId) ?: $contactId;

        return $number.'@c.us';
    }

    private function phoneNumberFromContactId(string $contactId): ?string
    {
        $number = preg_replace('/@.+$/', '', $contactId) ?: $contactId;
        $number = preg_replace('/:.+$/', '', $number) ?: $number;

        return preg_replace('/[^0-9]/', '', $number) ?: null;
    }
}
