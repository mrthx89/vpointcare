<?php

namespace App\Filament\Pages;

use App\Models\Ai\DraftPengetahuan;
use App\Services\Ai\InternalChatbotService;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\FilamentBreadcrumbs;
use App\Support\NavigationHelper;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class VPointAssistant extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.vpoint-assistant';

    public string $userMessage = '';

    public string $responseMode = 'fast';

    public string $knowledgeMode = 'all';

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachments = [];

    /** @var array<int, array<string, mixed>> */
    public array $messages = [];

    public bool $isTyping = false;

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-chat-bubble-bottom-center-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::CHATBOT_ACCESS, __('ui.navigation.operasional'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::CHATBOT_ACCESS, 15);
    }

    public function getTitle(): string | Htmlable
    {
        return __('ui.chatbot.title');
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::CHATBOT_ACCESS, __('ui.chatbot.navigation_label'));
    }

    public function getBreadcrumbs(): array
    {
        return FilamentBreadcrumbs::forMenu(AccessPermissions::CHATBOT_ACCESS, __('ui.chatbot.navigation_label'));
    }

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::CHATBOT_ACCESS)
            && NavigationHelper::isActive(AccessPermissions::CHATBOT_ACCESS);
    }

    public function mount(InternalChatbotService $chatbot): void
    {
        $this->loadHistory($chatbot);
    }

    public function sendMessage(InternalChatbotService $chatbot): void
    {
        $this->validate([
            'userMessage' => ['required', 'string', 'max:4000'],
            'responseMode' => ['required', 'in:light,fast'],
            'knowledgeMode' => ['required', 'in:all,none'],
            'attachments.*' => ['file', 'max:5120'],
        ], [
            'userMessage.required' => __('ui.chatbot.message_required'),
            'userMessage.max' => __('ui.chatbot.message_max', ['max' => 4000]),
        ]);

        $message = trim($this->userMessage);
        $attachmentContext = $this->prepareAttachments();
        $attachmentNames = array_column($attachmentContext, 'name');
        $this->userMessage = '';
        $this->attachments = [];
        $this->isTyping = true;

        $this->messages[] = [
            'role' => 'user',
            'content' => $message,
            'time' => now()->format('H:i'),
            'knowledge' => [],
            'attachments' => $attachmentNames,
            'response_mode' => $this->responseMode,
            'knowledge_mode' => $this->knowledgeMode,
        ];

        $result = $chatbot->ask($this->penggunaId(), $message, [
            'response_mode' => $this->responseMode,
            'knowledge_mode' => $this->knowledgeMode,
            'attachments' => $attachmentContext,
        ]);
        $this->isTyping = false;

        if (($result['ok'] ?? false) === true) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => (string) $result['reply'],
                'time' => now()->format('H:i'),
                'knowledge' => $result['knowledge_used'] ?? [],
                'message_id' => $result['message_id'] ?? null,
                'response_mode' => $result['response_mode'] ?? $this->responseMode,
                'knowledge_mode' => $result['knowledge_mode'] ?? $this->knowledgeMode,
            ];

            return;
        }

        $this->messages[] = [
            'role' => 'assistant',
            'content' => (string) ($result['error'] ?? __('ui.chatbot.error_provider_failed')),
            'time' => now()->format('H:i'),
            'knowledge' => [],
            'error' => true,
        ];
    }

    public function createKnowledgeDraft(int $index): void
    {
        abort_unless(FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE), 403);

        $message = $this->messages[$index] ?? null;

        if (! is_array($message) || ($message['role'] ?? '') !== 'assistant' || trim((string) ($message['content'] ?? '')) === '') {
            return;
        }

        $content = trim((string) $message['content']);
        $title = Str::limit($this->titleFromMarkdown($content), 250, '');
        $hash = hash('sha256', $content);

        if (! DB::table('TAiDraftPengetahuan')->where('HashKonten', $hash)->exists()) {
            DB::table('TAiDraftPengetahuan')->insert([
                'Id' => (string) Str::orderedUuid(),
                'IdChat' => null,
                'JudulDraft' => $title ?: __('ui.chatbot.draft_default_title'),
                'IsiDraft' => $content,
                'TagDraft' => implode(', ', array_filter((array) ($message['knowledge'] ?? []))) ?: 'vpoint-assistant',
                'KategoriDraft' => 'VPoint Assistant',
                'RingkasanSumber' => __('ui.chatbot.draft_source_summary'),
                'CuplikanSumberDisanitasi' => Str::limit(strip_tags($content), 2000, ''),
                'ConfidenceScore' => null,
                'StatusReview' => DraftPengetahuan::STATUS_DRAFT,
                'HashKonten' => $hash,
                'ProviderAi' => null,
                'ModelAi' => null,
                'PromptRingkas' => null,
                'ResponseJson' => json_encode([
                    'message_id' => $message['message_id'] ?? null,
                    'response_mode' => $message['response_mode'] ?? null,
                    'knowledge_mode' => $message['knowledge_mode'] ?? null,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'DibuatOlehAi' => true,
                'DibuatOleh' => Auth::id(),
                'TglBuat' => now(),
                'TglEdit' => now(),
            ]);
        }

        Notification::make()
            ->title(__('ui.chatbot.draft_created'))
            ->success()
            ->send();
    }

    public function clearHistory(InternalChatbotService $chatbot): void
    {
        $chatbot->clearHistory($this->penggunaId());
        $this->messages = [];
        $this->userMessage = '';
        $this->attachments = [];

        Notification::make()
            ->title(__('ui.chatbot.history_cleared'))
            ->success()
            ->send();
    }

    public function renderMarkdown(string $content): HtmlString
    {
        return new HtmlString((string) Str::of($content)->markdown([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    private function loadHistory(InternalChatbotService $chatbot): void
    {
        $this->messages = collect($chatbot->historyForDisplay($this->penggunaId()))
            ->map(function (object $row): array {
                $context = json_decode((string) ($row->KonteksJson ?? ''), true);

                return [
                    'role' => (string) $row->PeranPengirim,
                    'content' => (string) $row->IsiPesan,
                    'time' => \Illuminate\Support\Carbon::parse($row->TglBuat)->format('H:i'),
                    'knowledge' => is_array($context) ? ($context['knowledge_used'] ?? []) : [],
                    'attachments' => is_array($context) ? array_column((array) ($context['attachments'] ?? []), 'name') : [],
                    'message_id' => (string) ($row->Id ?? ''),
                    'response_mode' => is_array($context) ? ($context['response_mode'] ?? null) : null,
                    'knowledge_mode' => is_array($context) ? ($context['knowledge_mode'] ?? null) : null,
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<int, array{name: string, mime: string, size: int, content: string}> */
    private function prepareAttachments(): array
    {
        $files = [];

        foreach ($this->attachments as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            $mime = (string) ($file->getMimeType() ?: 'application/octet-stream');
            $content = $this->extractAttachmentText($file, $mime);

            $files[] = [
                'name' => $file->getClientOriginalName(),
                'mime' => $mime,
                'size' => (int) $file->getSize(),
                'content' => $content,
            ];
        }

        return $files;
    }

    private function extractAttachmentText(TemporaryUploadedFile $file, string $mime): string
    {
        $name = Str::lower($file->getClientOriginalName());
        $textLike = str_starts_with($mime, 'text/')
            || Str::endsWith($name, ['.txt', '.md', '.csv', '.json', '.log', '.sql', '.xml', '.yml', '.yaml']);

        if (! $textLike) {
            return __('ui.chatbot.attachment_binary_notice', ['name' => $file->getClientOriginalName(), 'mime' => $mime]);
        }

        $content = @file_get_contents($file->getRealPath()) ?: '';

        return Str::limit($content, 12000, '');
    }

    private function titleFromMarkdown(string $content): string
    {
        foreach (preg_split('/\R/u', $content) ?: [] as $line) {
            $line = trim(preg_replace('/^#+\s*/', '', $line) ?: '');

            if ($line !== '') {
                return $line;
            }
        }

        return __('ui.chatbot.draft_default_title');
    }

    private function penggunaId(): string
    {
        return (string) Auth::id();
    }
}