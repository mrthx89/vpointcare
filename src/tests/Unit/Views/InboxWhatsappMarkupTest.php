<?php

namespace Tests\Unit\Views;

use Tests\TestCase;

class InboxWhatsappMarkupTest extends TestCase
{
    public function test_inbox_alpine_root_attributes_do_not_contain_unescaped_double_quotes(): void
    {
        $view = file_get_contents(base_path('resources/views/filament/pages/inbox-whatsapp.blade.php'));

        self::assertIsString($view);
        self::assertStringNotContainsString('if ("Notification" in window)', $view);
        self::assertStringNotContainsString('this.notificationPermission = "unsupported"', $view);
        self::assertStringNotContainsString('json_encode(__(\'ui.pages.inbox.new_message_notification\'))', $view);
        self::assertStringContainsString('wacs-inbox-chat-list--drawer', $view);
        self::assertStringContainsString('wacs-inbox-aside--details', $view);
    }

    public function test_inbox_mobile_workspace_uses_compact_stats_and_keyboard_safe_composer(): void
    {
        $view = file_get_contents(base_path('resources/views/filament/pages/inbox-whatsapp.blade.php'));

        self::assertIsString($view);
        self::assertStringContainsString('wacs-inbox-stats', $view);
        self::assertStringContainsString('wacs-inbox-stats grid shrink-0 grid-cols-2', $view);
        self::assertStringContainsString('if ($event.ctrlKey || $event.metaKey)', $view);
        self::assertStringContainsString('aria-label="{{ __(\'ui.pages.inbox.open_details\') }}"', $view);
        self::assertStringNotContainsString('x-on:keydown.enter.prevent=', $view);
    }

    public function test_inbox_renders_refinement_modal_and_preference_control(): void
    {
        $view = file_get_contents(base_path("resources/views/filament/pages/inbox-whatsapp.blade.php"));
        self::assertStringContainsString("wire:click=\"setReplyRefinementPreference('follow')\"", $view);
        self::assertStringContainsString("wire:click=\"setReplyRefinementPreference('active')\"", $view);
        self::assertStringContainsString("wire:click=\"setReplyRefinementPreference('inactive')\"", $view);
        self::assertStringContainsString("wire:model=\"pengaturan.PerhalusJawabanWhatsappDefault\"", file_get_contents(base_path("resources/views/filament/pages/ai-agent.blade.php")));
    }
}
