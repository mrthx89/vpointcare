<?php

namespace App\Services\Waha;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class WahaSender
{
    /**
     * @return array{ok: bool, status?: int, body?: string, error?: string}
     */
    public function sendText(string $session, string $chatIdOrNumber, string $text, string $kodeIntegrasi = 'WAHA_SEND_TEXT'): array
    {
        $baseUrl = rtrim((string) config('services.waha.base_url'), '/');
        $path = '/' . ltrim((string) config('services.waha.send_text_path', '/api/sendText'), '/');
        $url = $baseUrl . $path;
        $payload = [
            'session' => $session,
            'chatId' => $this->normalizeChatId($chatIdOrNumber),
            'text' => $text,
        ];
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

    private function normalizeChatId(string $chatIdOrNumber): string
    {
        if (str_contains($chatIdOrNumber, '@')) {
            return $chatIdOrNumber;
        }

        $number = preg_replace('/[^0-9]/', '', $chatIdOrNumber) ?: $chatIdOrNumber;

        return $number . '@c.us';
    }
}
