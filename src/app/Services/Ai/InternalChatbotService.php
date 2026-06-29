<?php

namespace App\Services\Ai;

use App\Models\ChatbotMessage;
use App\Support\AiSettings;
use App\Support\SchemaCache;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class InternalChatbotService
{
    private const MAX_CONTEXT_MESSAGES = 20;
    private const KNOWLEDGE_SEARCH_LIMIT = 5;
    private const KNOWLEDGE_SEARCH_LIMIT_LIGHT = 2;

    public function ask(string $userId, string $message, array $options = []): array
    {
        $settings = AiSettings::get();

        if (! $settings) {
            return [
                'ok' => false,
                'error' => __('ui.chatbot.error_provider_missing'),
            ];
        }

        $message = Str::limit(trim($message), 4000, '');
        $mode = in_array(($options['response_mode'] ?? 'fast'), ['light', 'fast'], true) ? $options['response_mode'] : 'fast';
        $knowledgeMode = in_array(($options['knowledge_mode'] ?? 'all'), ['all', 'none'], true) ? $options['knowledge_mode'] : 'all';
        $attachments = is_array($options['attachments'] ?? null) ? $options['attachments'] : [];
        $knowledge = $knowledgeMode === 'none' ? [] : $this->searchKnowledge($message, $mode === 'light' ? self::KNOWLEDGE_SEARCH_LIMIT_LIGHT : self::KNOWLEDGE_SEARCH_LIMIT);
        $history = $this->conversationHistory($userId);
        $messages = $this->messagesForProvider($userId, $history, $knowledge, $message, $mode, $knowledgeMode, $attachments);

        DB::table('TChatbotInternal')->insert([
            'Id' => (string) Str::orderedUuid(),
            'IdPengguna' => $userId,
            'PeranPengirim' => ChatbotMessage::PERAN_USER,
            'IsiPesan' => $message,
            'KonteksJson' => json_encode([
                'response_mode' => $mode,
                'knowledge_mode' => $knowledgeMode,
                'attachments' => array_map(fn (array $file): array => Arr::only($file, ['name', 'mime', 'size']), $attachments),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'TglBuat' => now(),
        ]);

        try {
            $reply = $this->callProvider($settings, $messages);
        } catch (Throwable $exception) {
            Log::warning('VPoint Assistant AI provider failed.', [
                'provider' => (string) ($settings->ProviderAi ?? ''),
                'model' => (string) ($settings->ModelAi ?? ''),
                'error' => $this->safeError($exception->getMessage()),
            ]);

            return [
                'ok' => false,
                'error' => __('ui.chatbot.error_provider_failed'),
                'detail' => $this->safeError($exception->getMessage()),
            ];
        }

        if (trim($reply) === '') {
            return [
                'ok' => false,
                'error' => __('ui.chatbot.error_empty_response'),
            ];
        }

        $knowledgeTitles = array_values(array_filter(array_map(
            fn (object $row): string => (string) ($row->JudulPengetahuan ?? ''),
            $knowledge
        )));

        $parsed = $this->parseStructuredReply($reply);

        $assistantMessageId = (string) Str::orderedUuid();
        DB::table('TChatbotInternal')->insert([
            'Id' => $assistantMessageId,
            'IdPengguna' => $userId,
            'PeranPengirim' => ChatbotMessage::PERAN_ASSISTANT,
            'IsiPesan' => $parsed['visible'],
            'KonteksJson' => json_encode([
                'knowledge_used' => $knowledgeTitles,
                'response_mode' => $mode,
                'knowledge_mode' => $knowledgeMode,
                'suggested_replies' => $parsed['suggested'],
                'reasoning' => $parsed['reasoning'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'TglBuat' => now(),
        ]);

        return [
            'ok' => true,
            'reply' => $parsed['visible'],
            'reasoning' => $parsed['reasoning'],
            'suggested_replies' => $parsed['suggested'],
            'message_id' => $assistantMessageId,
            'knowledge_used' => $knowledgeTitles,
            'response_mode' => $mode,
            'knowledge_mode' => $knowledgeMode,
        ];
    }

    public function clearHistory(string $userId): int
    {
        return DB::table('TChatbotInternal')
            ->where('IdPengguna', $userId)
            ->delete();
    }

    /** @return array<int, object> */
    public function historyForDisplay(string $userId, int $limit = 50): array
    {
        return DB::table('TChatbotInternal')
            ->where('IdPengguna', $userId)
            ->orderByDesc('TglBuat')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->all();
    }

    /** @return array<int, object> */
    private function conversationHistory(string $userId): array
    {
        return DB::table('TChatbotInternal')
            ->where('IdPengguna', $userId)
            ->orderByDesc('TglBuat')
            ->limit(self::MAX_CONTEXT_MESSAGES)
            ->get()
            ->reverse()
            ->values()
            ->all();
    }

    /** @return array<int, object> */
    private function searchKnowledge(string $query, int $limit = self::KNOWLEDGE_SEARCH_LIMIT): array
    {
        $keywords = collect(preg_split('/\s+/u', Str::lower($query)) ?: [])
            ->map(fn (string $word): string => trim($word, " \t\n\r\0\x0B.,;:!?()[]{}\"'"))
            ->filter(fn (string $word): bool => mb_strlen($word) > 2)
            ->unique()
            ->take(8)
            ->values()
            ->all();

        if ($keywords === []) {
            return [];
        }

        $hasSearchKeywords = SchemaCache::hasColumn('MPengetahuan', 'SearchKeywords');
        $hasPriority = SchemaCache::hasColumn('MPengetahuan', 'PrioritasAi');

        return DB::table('MPengetahuan')
            ->where('NonAktif', false)
            ->where(function ($query) use ($keywords, $hasSearchKeywords): void {
                foreach ($keywords as $keyword) {
                    $query->orWhere('JudulPengetahuan', 'like', "%{$keyword}%")
                        ->orWhere('IsiPengetahuan', 'like', "%{$keyword}%")
                        ->orWhere('Tag', 'like', "%{$keyword}%");

                    if ($hasSearchKeywords) {
                        $query->orWhere('SearchKeywords', 'like', "%{$keyword}%");
                    }
                }
            })
            ->orderByDesc($hasPriority ? 'PrioritasAi' : 'JudulPengetahuan')
            ->limit($limit)
            ->get()
            ->all();
    }

    private function messagesForProvider(string $userId, array $history, array $knowledge, string $message, string $mode, string $knowledgeMode, array $attachments): array
    {
        $attachmentContext = $this->attachmentContext($attachments);

        $messages = [[
            'role' => 'system',
            'content' => $this->buildSystemPrompt($userId, $knowledge, $mode, $knowledgeMode, $attachmentContext),
        ]];

        foreach ($history as $row) {
            $messages[] = [
                'role' => $row->PeranPengirim === ChatbotMessage::PERAN_ASSISTANT ? 'assistant' : 'user',
                'content' => (string) $row->IsiPesan,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        return $messages;
    }

    private function buildSystemPrompt(string $userId, array $knowledge, string $mode, string $knowledgeMode, string $attachmentContext): string
    {
        $user = DB::table('MPengguna as p')
            ->leftJoin('MPeran as r', 'r.Id', '=', 'p.IdPeran')
            ->where('p.Id', $userId)
            ->select('p.NamaPengguna', 'r.NamaPeran', 'r.KodePeran')
            ->first();

        $knowledgeContext = '';

        if ($knowledge !== []) {
            $knowledgeContext = "\n\n=== KNOWLEDGE BASE ===\n";

            foreach ($knowledge as $row) {
                $title = Str::limit((string) $row->JudulPengetahuan, 200, '');
                $content = Str::limit((string) $row->IsiPengetahuan, 1800, '');
                $knowledgeContext .= "[{$title}]\n{$content}\n\n";
            }
        }

        $name = (string) ($user->NamaPengguna ?? 'User');
        $role = (string) ($user->NamaPeran ?? 'User');
        $modeInstruction = $mode === 'light'
            ? 'Mode jawaban: Ringan. Jawab lebih singkat, langsung ke langkah praktis.'
            : 'Mode jawaban: Cepat. Jawab praktis namun boleh lebih lengkap.';
        $knowledgeInstruction = $knowledgeMode === 'none'
            ? 'Mode knowledge: Tanpa Knowledge. Jangan pakai knowledge base.'
            : 'Mode knowledge: All Knowledge. Gunakan knowledge base yang relevan.';

        $reasoningRules = <<<'REASONING'

Setelah memberikan jawaban utama yang ramah dan praktis, tambahkan analisis internal terstruktur berikut:

**Goals:** [Apa tujuan utama percakapan ini - 1 kalimat]
**Constraints:** [Aturan/batasan yang tidak boleh dilanggar - max 2 poin]
**Context:** [Informasi yang sudah diketahui tentang user, role, percakapan - max 3 poin]
**Intent:** [Apa sebenarnya yang diminta/dibutuhkan user - 1 kalimat]
**Plan:** [Langkah-langkah menjawab - max 3 poin]
**Tools:** [Knowledge/RAG/database yang dipakai, atau "Tidak perlu"]

Kemudian berikan 3-4 opsi tindak lanjut untuk user:

**Selanjutnya:**
- [Opsi pertanyaan spesifik 1 untuk memperjelas atau melanjutkan]
- [Opsi pertanyaan spesifik 2]
- [Opsi pertanyaan spesifik 3]
- [Opsi pertanyaan spesifik 4 bila perlu]
REASONING;

        return <<<PROMPT
Anda adalah VPoint Assistant, chatbot internal untuk tim VPoint Care.

User aktif: {$name}
Role user: {$role}
{$modeInstruction}
{$knowledgeInstruction}

Aturan jawaban:
- Jawab dalam bahasa sesuai pertanyaan user, default Bahasa Indonesia.
- Gunakan gaya sopan, ringkas, praktis, dan profesional.
- Jika memakai knowledge base, rangkum dengan jelas dan jangan mengarang detail yang tidak ada.
- Jika tidak tahu, katakan tidak yakin dan sarankan cek menu terkait atau hubungi admin/supervisor.
- Jangan menampilkan API key, token, password, atau rahasia teknis.
- Format jawaban utama dengan Markdown rapi. Pakai heading, bullet, tabel, dan fenced code block bila membantu.
{$reasoningRules}
{$knowledgeContext}
{$attachmentContext}
PROMPT;
    }

    private function attachmentContext(array $attachments): string
    {
        if ($attachments === []) {
            return '';
        }

        $context = "\n\n=== FILE USER ===\n";

        foreach ($attachments as $file) {
            $name = Str::limit((string) ($file['name'] ?? 'file'), 180, '');
            $mime = (string) ($file['mime'] ?? 'unknown');
            $content = Str::limit((string) ($file['content'] ?? ''), 5000, '');
            $context .= "[{$name}] {$mime}\n{$content}\n\n";
        }

        return $context;
    }

    /** @return array{visible: string, reasoning: string, suggested: array<int, string>} */
    private function parseStructuredReply(string $reply): array
    {
        $suggested = [];
        $reasoning = '';
        $visible = $reply;

        // Extract "Selanjutnya:" section for suggested replies
        if (preg_match('/\*\*Selanjutnya:\*\*\s*(.+?)(?:$|\*\*Response)/s', $reply, $m)) {
            preg_match_all('/^\s*[-*]\s+(.+)$/m', $m[1], $options);
            if (! empty($options[1])) {
                $suggested = array_map('trim', $options[1]);
                $suggested = array_slice($suggested, 0, 4);
            }
        }

        // Extract reasoning between **Goals:** and **Selanjutnya:** (or end)
        if (preg_match('/(\*\*Goals:\*\*[\s\S]*?)(?=\*\*Selanjutnya:|\*\*Response:|\z)/', $reply, $rm)) {
            $reasoning = trim($rm[1]);
        }

        // Remove reasoning + suggested blocks from visible reply
        $visible = preg_replace([
            '/\*\*Goals:\*\*[\s\S]*?(?=\*\*Selanjutnya:|\*\*Response:|\z)/s',
            '/\*\*Selanjutnya:\*\*[\s\S]*?(?=\*\*Response:|\z)/s',
        ], '', $reply);
        $visible = trim($visible);

        return [
            'visible' => $visible ?: $reply,
            'reasoning' => $reasoning,
            'suggested' => $suggested,
        ];
    }


    private function assistantModel(object $settings): string
    {
        $provider = strtolower((string) $settings->ProviderAi);

        if ($provider === 'openai') {
            $configKey = 'openai';
        } elseif (in_array($provider, ['9router', 'ninerouter'], true)) {
            $configKey = 'ninerouter';
        } else {
            $configKey = $provider;
        }

        return $settings->ModelInstructAi
            ?? $settings->ModelAi
            ?? (string) config("services.{$configKey}.model")
            ?? '';
    }

    /** @param array<int, array{role: string, content: string}> $messages */
    private function callProvider(object $settings, array $messages): string
    {
        $provider = strtolower((string) $settings->ProviderAi);
        $apiKey = $this->apiKey($settings, $provider);

        if (! $apiKey) {
            throw new RuntimeException(__('ui.chatbot.error_provider_missing'));
        }

        $model = $this->assistantModel($settings);

        if ($provider === 'openai') {
            $systemPrompt = (string) Arr::get($messages, '0.content', '');
            $conversation = $this->messagesToTranscript(array_slice($messages, 1));
            $baseUrl = rtrim((string) ($settings->BaseUrl ?: config('services.openai.base_url')), '/');
            $endpoint = str_ends_with($baseUrl, '/responses') ? $baseUrl : $baseUrl.'/responses';

            $response = Http::withToken($apiKey)->acceptJson()->asJson()->timeout(30)->post($endpoint, [
                'model' => $model ?: config('services.openai.model'),
                'instructions' => $systemPrompt,
                'input' => $conversation,
                'store' => false,
            ]);

            $payload = $response->json();
            $text = trim((string) (Arr::get($payload, 'output_text') ?: Arr::get($payload, 'output.0.content.0.text')));
        } else {
            $key = in_array($provider, ['9router', 'ninerouter'], true) ? 'ninerouter' : $provider;
            $baseUrl = rtrim((string) ($settings->BaseUrl ?: config("services.{$key}.base_url")), '/');
            $endpoint = str_ends_with($baseUrl, '/chat/completions') ? $baseUrl : $baseUrl.'/chat/completions';
            $request = Http::withToken($apiKey)->acceptJson()->asJson()->timeout(30);

            if (in_array($key, ['openrouter', 'ninerouter'], true)) {
                $request = $request->withHeaders(array_filter([
                    'HTTP-Referer' => config("services.{$key}.site_url"),
                    'X-Title' => config("services.{$key}.site_name"),
                ]));
            }

            $response = $request->post($endpoint, [
                'model' => $model ?: config("services.{$key}.model"),
                'messages' => $messages,
                'stream' => false,
            ]);

            $payload = $response->json();
            $text = trim((string) Arr::get($payload, 'choices.0.message.content', ''));
        }

        if (! $response->successful()) {
            throw new RuntimeException(__('ui.chatbot.error_provider_failed'));
        }

        if ($text === '') {
            throw new RuntimeException(__('ui.chatbot.error_empty_response'));
        }

        return $text;
    }

    private function messagesToTranscript(array $messages): string
    {
        return collect($messages)
            ->map(function (array $message): string {
                $role = match ($message['role'] ?? 'user') {
                    'assistant' => 'Assistant',
                    default => 'User',
                };

                return $role.': '.trim((string) ($message['content'] ?? ''));
            })
            ->filter(fn (string $line): bool => $line !== 'User:' && $line !== 'Assistant:')
            ->implode("\n\n");
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
        return preg_replace('/Bearer\s+[A-Za-z0-9._\-]+|sk-[A-Za-z0-9._\-]+/i', '[secret]', $message)
            ?: __('ui.chatbot.error_provider_failed');
    }
}