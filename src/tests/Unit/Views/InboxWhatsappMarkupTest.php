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
}
