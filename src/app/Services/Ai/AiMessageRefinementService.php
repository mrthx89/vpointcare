<?php

namespace App\Services\Ai;

use App\Support\AiSettings;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiMessageRefinementService
{
    private const FALLBACK_AI_SIGNATURES = [
        '~ Auto Reply by VICA',
        '~ AI',
        '~AI',
    ];

    public function __construct(private readonly AiAutoReplyService $aiAutoReplyService) {}

    /**
     * @return array{ok: bool, text?: string, error?: string}
     */
    public function refine(string $message): array
    {
        $message = trim($message);

        if ($message === '') {
            return ['ok' => false, 'error' => 'Pesan tidak boleh kosong.'];
        }

        $settings = AiSettings::get();

        if (! $settings || ! ($settings->ProviderAi ?? null)) {
            return ['ok' => false, 'error' => 'Konfigurasi AI Agent belum diatur.'];
        }

        try {
            $refined = $this->aiAutoReplyService->generateManualRefinement($settings, $message);

            $text = $this->removeTrailingSignature($settings, (string) ($refined['text'] ?? ''));

            if ($refined && $text !== '') {
                return ['ok' => true, 'text' => $text];
            }

            return ['ok' => false, 'error' => 'AI tidak mengembalikan respons yang valid.'];
        } catch (Throwable $e) {
            Log::warning('AI Message Refinement failed', [
                'provider' => $settings->ProviderAi,
                'exception' => $e::class,
            ]);

            return ['ok' => false, 'error' => 'Gagal menghubungi AI provider.'];
        }
    }

    private function removeTrailingSignature(object $settings, string $text): string
    {
        $text = trim($text);
        $signatures = array_values(array_unique(array_filter([
            trim((string) ($settings->TandaTanganAi ?? '')),
            ...self::FALLBACK_AI_SIGNATURES,
        ])));

        foreach ($signatures as $signature) {
            if (str_ends_with($text, $signature)) {
                $text = trim(substr($text, 0, -strlen($signature)));
            }
        }

        return $text;
    }
}
