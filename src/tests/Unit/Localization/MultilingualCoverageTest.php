<?php

namespace Tests\Unit\Localization;

use Tests\TestCase;

class MultilingualCoverageTest extends TestCase
{
    public function test_whatsapp_inbox_locale_copy_is_complete_and_translated(): void
    {
        $id = require base_path('resources/lang/id/ui.php');
        $en = require base_path('resources/lang/en/ui.php');

        $requiredKeys = [
            'title',
            'navigation_label',
            'notifications_toggle',
            'notifications_enable',
            'notifications_active',
            'notifications_denied',
            'notifications_unsupported',
            'new_message_notification',
            'details',
            'open_details',
            'ai_auto_reply',
            'chat_settings',
            'unread_messages',
            'you',
            'refresh_mapping',
            'reverb_status_connecting',
            'reverb_status_connected',
            'reverb_status_disconnected',
            'reverb_status_unavailable',
            'reverb_status_failed',
            'reverb_status_initialized',
            'status_new',
            'waiting_cs',
            'status_in_progress',
            'status_waiting_customer',
            'status_completed',
            'status_closed',
            'refinement_provider_unavailable',
        ];

        foreach ($requiredKeys as $key) {
            self::assertArrayHasKey($key, $id['pages']['inbox']);
            self::assertArrayHasKey($key, $en['pages']['inbox']);
            self::assertNotSame($id['pages']['inbox'][$key], $en['pages']['inbox'][$key], $key);
        }

        self::assertSame('Chat Details', $en['pages']['inbox']['open_details']);
        self::assertSame('Unread', $en['pages']['inbox']['unread_messages']);
        self::assertSame('AI auto reply', $en['pages']['inbox']['ai_auto_reply']);
    }

    public function test_ai_agent_provider_summaries_have_locale_specific_copy(): void
    {
        $id = require base_path('resources/lang/id/ui.php');
        $en = require base_path('resources/lang/en/ui.php');
        $page = file_get_contents(base_path('app/Filament/Pages/AiAgent.php'));

        foreach (['openai', 'deepseek', 'openrouter', 'ninerouter'] as $provider) {
            $key = 'provider_' . $provider . '_summary';

            self::assertArrayHasKey($key, $id['pages']['ai_agent']);
            self::assertArrayHasKey($key, $en['pages']['ai_agent']);
            self::assertNotSame($id['pages']['ai_agent'][$key], $en['pages']['ai_agent'][$key], $key);
            self::assertStringContainsString('ui.pages.ai_agent.' . $key, $page);
        }

        self::assertSame('Test result', $en['pages']['ai_agent']['test_result']);
        self::assertSame('Hasil tes', $id['pages']['ai_agent']['test_result']);
        self::assertStringContainsString('ui.pages.ai_agent.test_prompt_default', $page);
        self::assertStringNotContainsString('Stabil untuk customer service.', $page);
    }

    public function test_inbox_markup_uses_localized_status_and_ui_copy(): void
    {
        $page = file_get_contents(base_path('app/Filament/Pages/InboxWhatsapp.php'));
        $view = file_get_contents(base_path('resources/views/filament/pages/inbox-whatsapp.blade.php'));
        $provider = file_get_contents(base_path('app/Providers/Filament/AdminPanelProvider.php'));
        $echo = file_get_contents(base_path('resources/js/echo.js'));

        self::assertStringContainsString('s.KodeStatusChat', $page);
        self::assertStringContainsString('StatusCode', $page);
        self::assertStringContainsString('chatStatusLabel', $page);
        self::assertStringContainsString('ui.pages.inbox.title', $page);
        self::assertStringContainsString('ui.pages.inbox.navigation_label', $page);
        self::assertStringContainsString('ui.pages.waha_connection.status_running', $view);
        self::assertStringContainsString('ui.pages.inbox.open_details', $view);
        self::assertStringContainsString('wacsReverbCopy', $provider);
        self::assertStringContainsString('wacsReverbCopy', $echo);

        self::assertStringNotContainsString('AI Auto Reply</div>', $view);
        self::assertStringNotContainsString('title=' . chr(34) . 'Pengaturan' . chr(34), $view);
        self::assertStringNotContainsString('}} unread</div>', $view);
        self::assertStringNotContainsString('Reverb client belum terdeteksi.', $view);
        self::assertStringNotContainsString('Reverb client sedang mencoba tersambung.', $echo);
        self::assertStringNotContainsString('Ada pesan WhatsApp baru', $echo);
    }
}
