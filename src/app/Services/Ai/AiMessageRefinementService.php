<?php

namespace App\Services\Ai;

use App\Support\AiSettings;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiMessageRefinementService
{
    public function __construct(private readonly AiAutoReplyService $aiAutoReplyService)
    {
    }

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

            if ($refined && trim($refined['text'] ?? '') !== '') {
                return ['ok' => true, 'text' => trim($refined['text'])];
            }

            return ['ok' => false, 'error' => 'AI tidak mengembalikan respons yang valid.'];
        } catch (Throwable $e) {
            Log::warning('AI Message Refinement failed', [
                'provider' => $settings->ProviderAi,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'Gagal menghubungi AI provider.'];
        }
    }
}