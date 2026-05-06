<?php

namespace App\Support;

class AccessPermissions
{
    public const DASHBOARD_VIEW = 'dashboard.view';

    public const INBOX_VIEW = 'inbox.view';

    public const INBOX_REPLY = 'inbox.reply';

    public const INBOX_MANAGE = 'inbox.manage';

    public const TICKET_VIEW = 'ticket.view';

    public const TICKET_MANAGE = 'ticket.manage';

    public const AI_AGENT_VIEW = 'ai_agent.view';

    public const AI_AGENT_MANAGE = 'ai_agent.manage';

    public const LOG_DATA_VIEW = 'log_data.view';

    public const MASTER_CUSTOMER_VIEW = 'master_customer.view';

    public const MASTER_CUSTOMER_MANAGE = 'master_customer.manage';

    public const KNOWLEDGE_VIEW = 'knowledge.view';

    public const KNOWLEDGE_MANAGE = 'knowledge.manage';

    public const HOLIDAY_VIEW = 'holiday.view';

    public const HOLIDAY_MANAGE = 'holiday.manage';

    public const USER_VIEW = 'user.view';

    public const USER_MANAGE = 'user.manage';

    public const CHAT_HISTORY_VIEW = 'chat_history.view';

    /**
     * @return array<string, array{label: string, module: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            self::DASHBOARD_VIEW => [
                'label' => 'Lihat Dasbor',
                'module' => 'Dashboard',
                'description' => 'Melihat ringkasan aktivitas customer service.',
            ],
            self::INBOX_VIEW => [
                'label' => 'Lihat Inbox WhatsApp',
                'module' => 'Inbox WhatsApp',
                'description' => 'Melihat daftar dan detail percakapan WhatsApp.',
            ],
            self::INBOX_REPLY => [
                'label' => 'Balas Inbox WhatsApp',
                'module' => 'Inbox WhatsApp',
                'description' => 'Mengirim balasan WhatsApp dan menyimpan draft balasan.',
            ],
            self::INBOX_MANAGE => [
                'label' => 'Kelola Inbox WhatsApp',
                'module' => 'Inbox WhatsApp',
                'description' => 'Mengelola mapping, catatan internal, penutupan chat, dan kontrol sesi.',
            ],
            self::TICKET_VIEW => [
                'label' => 'Lihat Ticketing',
                'module' => 'Ticketing',
                'description' => 'Melihat menu dan halaman ticketing.',
            ],
            self::TICKET_MANAGE => [
                'label' => 'Kelola Ticketing',
                'module' => 'Ticketing',
                'description' => 'Membuat, memperbarui, dan menangani ticket.',
            ],
            self::AI_AGENT_VIEW => [
                'label' => 'Lihat AI Agent',
                'module' => 'AI Agent',
                'description' => 'Melihat pengaturan AI Agent.',
            ],
            self::AI_AGENT_MANAGE => [
                'label' => 'Kelola AI Agent',
                'module' => 'AI Agent',
                'description' => 'Mengubah API key, prompt, template, jam kerja, dan notifikasi AI Agent.',
            ],
            self::LOG_DATA_VIEW => [
                'label' => 'Lihat Log Data',
                'module' => 'Monitoring',
                'description' => 'Melihat log integrasi dan webhook.',
            ],
            self::MASTER_CUSTOMER_VIEW => [
                'label' => 'Lihat Master Customer',
                'module' => 'Master Customer',
                'description' => 'Melihat ringkasan dan data master customer.',
            ],
            self::MASTER_CUSTOMER_MANAGE => [
                'label' => 'Kelola Master Customer',
                'module' => 'Master Customer',
                'description' => 'Membuat, mengubah, dan sinkron data customer, nomor, dan grup WhatsApp.',
            ],
            self::KNOWLEDGE_VIEW => [
                'label' => 'Lihat Knowledge Base AI',
                'module' => 'Knowledge Base AI',
                'description' => 'Melihat knowledge base untuk AI Agent.',
            ],
            self::KNOWLEDGE_MANAGE => [
                'label' => 'Kelola Knowledge Base AI',
                'module' => 'Knowledge Base AI',
                'description' => 'Membuat dan mengubah knowledge base untuk AI Agent.',
            ],
            self::HOLIDAY_VIEW => [
                'label' => 'Lihat Hari Libur',
                'module' => 'Hari Libur',
                'description' => 'Melihat master hari libur.',
            ],
            self::HOLIDAY_MANAGE => [
                'label' => 'Kelola Hari Libur',
                'module' => 'Hari Libur',
                'description' => 'Membuat dan mengubah master hari libur.',
            ],
            self::USER_VIEW => [
                'label' => 'Lihat User Login',
                'module' => 'User Login',
                'description' => 'Melihat daftar user login.',
            ],
            self::USER_MANAGE => [
                'label' => 'Kelola User Login',
                'module' => 'User Login',
                'description' => 'Membuat, mengubah, approve, block, pending, dan assign role user.',
            ],
            self::CHAT_HISTORY_VIEW => [
                'label' => 'Lihat History Chat',
                'module' => 'History Chat',
                'description' => 'Membuka detail riwayat sesi chat.',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function defaultRolePermissions(): array
    {
        return [
            'ADMIN' => self::codes(),
            'SUPERVISOR_CS' => [
                self::DASHBOARD_VIEW,
                self::INBOX_VIEW,
                self::INBOX_REPLY,
                self::INBOX_MANAGE,
                self::TICKET_VIEW,
                self::TICKET_MANAGE,
                self::AI_AGENT_VIEW,
                self::AI_AGENT_MANAGE,
                self::LOG_DATA_VIEW,
                self::MASTER_CUSTOMER_VIEW,
                self::MASTER_CUSTOMER_MANAGE,
                self::KNOWLEDGE_VIEW,
                self::KNOWLEDGE_MANAGE,
                self::HOLIDAY_VIEW,
                self::HOLIDAY_MANAGE,
                self::CHAT_HISTORY_VIEW,
            ],
            'CS' => [
                self::DASHBOARD_VIEW,
                self::INBOX_VIEW,
                self::INBOX_REPLY,
                self::TICKET_VIEW,
                self::TICKET_MANAGE,
                self::MASTER_CUSTOMER_VIEW,
                self::CHAT_HISTORY_VIEW,
            ],
            'DEVELOPER' => [
                self::DASHBOARD_VIEW,
                self::TICKET_VIEW,
                self::TICKET_MANAGE,
                self::LOG_DATA_VIEW,
                self::CHAT_HISTORY_VIEW,
            ],
            'VIEWER' => [
                self::DASHBOARD_VIEW,
                self::CHAT_HISTORY_VIEW,
            ],
        ];
    }

    /**
     * @return array<string, array{name: string, description: string}>
     */
    public static function defaultRoles(): array
    {
        return [
            'ADMIN' => [
                'name' => 'Admin',
                'description' => 'Akses penuh aplikasi',
            ],
            'SUPERVISOR_CS' => [
                'name' => 'Supervisor CS',
                'description' => 'Monitoring dan pengaturan customer service',
            ],
            'CS' => [
                'name' => 'Customer Service',
                'description' => 'Menangani chat dan membuat ticket',
            ],
            'DEVELOPER' => [
                'name' => 'Developer',
                'description' => 'Menangani ticket teknis',
            ],
            'VIEWER' => [
                'name' => 'Viewer',
                'description' => 'Melihat dashboard dan laporan',
            ],
        ];
    }
}
