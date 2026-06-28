<?php

namespace App\Filament\Pages;

use App\Services\Ai\InternalChatbotService;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\FilamentBreadcrumbs;
use App\Support\NavigationHelper;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class VPointAssistant extends Page
{
    protected string $view = 'filament.pages.vpoint-assistant';

    public string $userMessage = '';

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

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
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
            'userMessage' => ['required', 'string', 'max:2000'],
        ], [
            'userMessage.required' => __('ui.chatbot.message_required'),
            'userMessage.max' => __('ui.chatbot.message_max', ['max' => 2000]),
        ]);

        $message = trim($this->userMessage);
        $this->userMessage = '';
        $this->isTyping = true;

        $this->messages[] = [
            'role' => 'user',
            'content' => $message,
            'time' => now()->format('H:i'),
            'knowledge' => [],
        ];

        $result = $chatbot->ask($this->penggunaId(), $message);
        $this->isTyping = false;

        if (($result['ok'] ?? false) === true) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => (string) $result['reply'],
                'time' => now()->format('H:i'),
                'knowledge' => $result['knowledge_used'] ?? [],
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

    public function clearHistory(InternalChatbotService $chatbot): void
    {
        $chatbot->clearHistory($this->penggunaId());
        $this->messages = [];
        $this->userMessage = '';

        Notification::make()
            ->title(__('ui.chatbot.history_cleared'))
            ->success()
            ->send();
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
                ];
            })
            ->values()
            ->all();
    }

    private function penggunaId(): string
    {
        return (string) Auth::id();
    }
}
