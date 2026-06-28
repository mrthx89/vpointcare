<?php

namespace App\Services\Ai;

use App\Support\AiSettings;
use App\Support\SchemaCache;

use App\Models\Ai\DraftPengetahuan;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiKnowledgeLearningService
{
    /**
     * @return array<string, mixed>
     */
    public function createDraftFromChat(string $chatId, ?string $userId = null): array
    {
        $chat = DB::table('TChat as c')
            ->leftJoin('MCustomer as m', 'm.Id', '=', 'c.IdCustomer')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->where('c.Id', $chatId)
            ->select('c.*', 'm.NamaCustomer', 'i.NamaInstansi')
            ->first();

        if (! $chat) {
            return ['ok' => false, 'reason' => __('ui.ai_learning.chat_not_found')];
        }

        $messages = DB::table('TChatD')
            ->where('IdChat', $chatId)
            ->whereNotNull('IsiPesan')
            ->where('IsiPesan', '<>', '')
            ->orderByDesc('TglPesan')
            ->limit(40)
            ->get()
            ->reverse()
            ->values();

        if ($messages->count() < 2) {
            return ['ok' => false, 'reason' => __('ui.ai_learning.not_enough_messages')];
        }

        $context = $messages->map(function (object $row): string {
            $speaker = $row->ArahPesan === 'Keluar'
                ? ((bool) ($row->DihasilkanOlehAi ?? false) ? 'AI Agent' : 'Customer Service')
                : 'Customer';

            return $speaker . ': ' . $this->sanitizeText((string) $row->IsiPesan);
        })->implode("\n");

        $context = Str::limit($context, 12000, '');
        $settings = $this->settings();

        if (! $settings) {
            return ['ok' => false, 'reason' => __('ui.ai_learning.settings_missing')];
        }

        try {
            $candidate = $this->extractCandidate($settings, $chat, $context);
        } catch (Throwable $e) {
            return ['ok' => false, 'reason' => $this->safeError($e->getMessage())];
        }

        if (! (bool) ($candidate['layak'] ?? false)) {
            return ['ok' => false, 'reason' => (string) ($candidate['alasan'] ?? __('ui.ai_learning.not_reusable'))];
        }

        $title = trim((string) ($candidate['judul'] ?? ''));
        $content = trim((string) ($candidate['isi'] ?? ''));

        if (mb_strlen($title) < 8 || mb_strlen($content) < 30) {
            return ['ok' => false, 'reason' => __('ui.ai_learning.too_short')];
        }

        $hash = hash('sha256', Str::lower(Str::squish($title . ' ' . $content)));
        $duplicate = DB::table('TAiDraftPengetahuan')->where('HashKonten', $hash)->where('StatusReview', '<>', DraftPengetahuan::STATUS_REJECTED)->exists();

        if ($duplicate) {
            return ['ok' => false, 'reason' => __('ui.ai_learning.duplicate_draft')];
        }

        $draftId = (string) Str::orderedUuid();
        DB::table('TAiDraftPengetahuan')->insert([
            'Id' => $draftId,
            'IdChat' => $chat->Id,
            'IdCustomer' => $chat->IdCustomer ?? null,
            'IdInstansi' => $chat->IdInstansi ?? null,
            'JudulDraft' => Str::limit($this->sanitizeText($title), 255, ''),
            'IsiDraft' => $this->sanitizeText($content),
            'TagDraft' => Str::limit($this->sanitizeText((string) ($candidate['tag'] ?? '')), 500, ''),
            'KategoriDraft' => Str::limit($this->sanitizeText((string) ($candidate['kategori'] ?? '')), 100, ''),
            'RingkasanSumber' => $this->sanitizeText((string) ($candidate['ringkasan_sumber'] ?? '')),
            'CuplikanSumberDisanitasi' => Str::limit($context, 6000, ''),
            'ConfidenceScore' => max(0, min(100, (float) ($candidate['confidence'] ?? 0))),
            'StatusReview' => DraftPengetahuan::STATUS_DRAFT,
            'HashKonten' => $hash,
            'ProviderAi' => $settings->ProviderAi ?: 'OpenAI',
            'ModelAi' => $settings->ModelAi,
            'PromptRingkas' => Str::limit($this->buildExtractionPrompt($chat, $context), 4000, ''),
            'ResponseJson' => Str::limit(json_encode($candidate, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', 8000, ''),
            'DibuatOlehAi' => true,
            'DibuatOleh' => $userId,
            'TglBuat' => now(),
        ]);

        return ['ok' => true, 'draft_id' => $draftId, 'title' => $title, 'message' => __('ui.ai_learning.draft_created_message')];
    }

    /** @return array<string, mixed> */
    private function extractCandidate(object $settings, object $chat, string $context): array
    {
        $prompt = $this->buildExtractionPrompt($chat, $context);
        $text = $this->callProvider($settings, $prompt);
        $json = trim($text);
        $json = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $json) ?: $json;
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException(__('ui.ai_learning.invalid_json'));
        }

        return $decoded;
    }

    private function buildExtractionPrompt(object $chat, string $context): string
    {
        $customer = $chat->NamaInstansi ?: $chat->NamaCustomer ?: 'Belum dipetakan';

        return trim("Anda mengekstrak knowledge base customer service dari chat WhatsApp.\n"
            . "Keluarkan JSON valid saja, tanpa markdown.\n"
            . "Ambil hanya fakta/prosedur reusable yang terbukti dari chat. Jangan mengarang. Jangan masukkan data pribadi, nomor, email, alamat, token, password, OTP, atau janji khusus customer.\n"
            . "Jika tidak ada knowledge reusable, balas {\"layak\":false,\"alasan\":\"...\"}.\n"
            . "Jika layak, format: {\"layak\":true,\"judul\":\"...\",\"isi\":\"...\",\"tag\":\"tag1, tag2\",\"kategori\":\"...\",\"confidence\":80,\"ringkasan_sumber\":\"...\"}.\n\n"
            . "Customer/Instansi: {$customer}\nJenis chat: {$chat->JenisChat}\n\nRiwayat tersanitasi:\n{$context}");
    }

    public function sanitizeText(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', '[email]', $text) ?? $text;
        $text = preg_replace('/\b(?:\+?62|0)?\d[\d\s\-]{8,}\d\b/u', '[nomor]', $text) ?? $text;
        $text = preg_replace('/\b(?:otp|kode verifikasi)\s*[:=]?\s*\d{4,8}\b/iu', '[otp]', $text) ?? $text;
        $text = preg_replace('/\b(?:password|passwd|token|api[_\s-]?key|secret)\s*[:=]\s*\S+/iu', '[rahasia]', $text) ?? $text;
        $text = preg_replace('/https?:\/\/\S*(?:token|key|secret|session|auth)\S*/iu', '[url]', $text) ?? $text;
        $text = preg_replace('/\b\d{16}\b/u', '[nomor_identitas]', $text) ?? $text;

        return trim($text);
    }

    private function settings(): ?object
    {
        return AiSettings::get();
    }

    private function callProvider(object $settings, string $prompt): string
    {
        $provider = strtolower((string) $settings->ProviderAi);
        $apiKey = $this->apiKey($settings, $provider);

        if (! $apiKey) {
            throw new RuntimeException(__('ui.ai_learning.api_key_missing'));
        }

        if ($provider === 'openai') {
            $response = Http::withToken($apiKey)->acceptJson()->asJson()->timeout(45)->post($settings->BaseUrl ?: config('services.openai.base_url'), [
                'model' => $settings->ModelAi ?: config('services.openai.model'),
                'instructions' => 'Anda adalah ekstraktor knowledge base. Output JSON valid saja.',
                'input' => $prompt,
                'store' => false,
            ]);
            $payload = $response->json();
            $text = trim((string) (Arr::get($payload, 'output_text') ?: Arr::get($payload, 'output.0.content.0.text')));
        } else {
            $key = in_array($provider, ['9router', 'ninerouter'], true) ? 'ninerouter' : $provider;
            $baseUrl = rtrim((string) ($settings->BaseUrl ?: config("services.{$key}.base_url")), '/');
            $endpoint = str_ends_with($baseUrl, '/chat/completions') ? $baseUrl : $baseUrl . '/chat/completions';
            $request = Http::withToken($apiKey)->acceptJson()->asJson()->timeout(45);
            if (in_array($key, ['openrouter', 'ninerouter'], true)) {
                $request = $request->withHeaders(array_filter([
                    'HTTP-Referer' => config("services.{$key}.site_url"),
                    'X-Title' => config("services.{$key}.site_name"),
                ]));
            }
            $response = $request->post($endpoint, [
                'model' => $settings->ModelAi ?: config("services.{$key}.model"),
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda adalah ekstraktor knowledge base. Output JSON valid saja.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'stream' => false,
            ]);
            $payload = $response->json();
            $text = trim((string) Arr::get($payload, 'choices.0.message.content', ''));
        }

        if (! $response->successful()) {
            throw new RuntimeException(__('ui.ai_learning.provider_failed', ['status' => $response->status()]));
        }

        if ($text === '') {
            throw new RuntimeException(__('ui.ai_learning.empty_response'));
        }

        return $text;
    }

    private function apiKey(object $settings, string $provider): ?string
    {
        $column = match ($provider) {
            'deepseek' => 'DeepSeekApiKeyTerenkripsi',
            'openrouter' => 'OpenRouterApiKeyTerenkripsi',
            '9router', 'ninerouter' => 'NineRouterApiKeyTerenkripsi',
            default => 'OpenAiApiKeyTerenkripsi',
        };
        $encrypted = $settings->{$column} ?? ($provider === 'openai' ? ($settings->ApiKeyTerenkripsi ?? null) : null);
        if ($encrypted) {
            try {
                return Crypt::decryptString($encrypted);
            } catch (Throwable) {
                return null;
            }
        }

        return match ($provider) {
            'deepseek' => config('services.deepseek.api_key'),
            'openrouter' => config('services.openrouter.api_key'),
            '9router', 'ninerouter' => config('services.ninerouter.api_key'),
            default => config('services.openai.api_key'),
        };
    }

    private function safeError(string $message): string
    {
        return preg_replace('/Bearer\s+[A-Za-z0-9._\-]+|sk-[A-Za-z0-9._\-]+/i', '[secret]', $message) ?: __('ui.ai_learning.draft_not_created_title');
    }
}
