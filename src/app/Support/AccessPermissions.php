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

    public const HAK_AKSES_VIEW = 'hak_akses.view';

    public const HAK_AKSES_MANAGE = 'hak_akses.manage';

    public const JOB_SCHEDULE_VIEW = 'job_schedule.view';

    public const MENU_MASTER_INSTANSI = 'menu.master.instansi';

    public const MENU_MASTER_CUSTOMER = 'menu.master.customer';

    public const MENU_MASTER_NOMOR_WHATSAPP = 'menu.master.nomor_whatsapp';

    public const MENU_MASTER_GRUP_WHATSAPP = 'menu.master.grup_whatsapp';

    public const MENU_MASTER_ANGGOTA_GRUP = 'menu.master.anggota_grup';

    /**
     * @return array<string, array{label: string, module: string, description: string}>
     */
    public static function definitions(?string $locale = null): array
    {
        return self::mapDefinitions($locale ?: app()->getLocale());
    }

    /**
     * @return array<string, array{label_id: string, label_en: string, module_id: string, module_en: string, description_id: string, description_en: string}>
     */
    public static function localizedDefinitions(): array
    {
        $localized = [];

        foreach (self::definitionKeys() as $code => $keys) {
            $localized[$code] = [
                'label_id' => self::translate($keys['label'], 'id'),
                'label_en' => self::translate($keys['label'], 'en'),
                'module_id' => self::translate($keys['module'], 'id'),
                'module_en' => self::translate($keys['module'], 'en'),
                'description_id' => self::translate($keys['description'], 'id'),
                'description_en' => self::translate($keys['description'], 'en'),
            ];
        }

        return $localized;
    }

    /**
     * @return array{label: string, module: string, description: string}
     */
    public static function localizedColumnNames(?string $locale = null): array
    {
        $suffix = LocaleManager::normalize($locale ?: app()->getLocale()) === 'en' ? 'En' : 'Id';

        return [
            'label' => "NamaHakAkses{$suffix}",
            'module' => "Modul{$suffix}",
            'description' => "Keterangan{$suffix}",
        ];
    }

    /**
     * @return array<string, array{label_id: string, label_en: string, description_id: string, description_en: string, sort: int, icon: string}>
     */
    public static function sidebarGroups(): array
    {
        return [
            'operasional' => [
                'label_id' => self::translate('ui.navigation.operasional', 'id'),
                'label_en' => self::translate('ui.navigation.operasional', 'en'),
                'description_id' => 'Group menu untuk operasional customer service.',
                'description_en' => 'Menu group for customer service operations.',
                'sort' => 10,
                'icon' => 'heroicon-o-chat-bubble-left-right',
            ],
            'assistant' => [
                'label_id' => self::translate('ui.navigation.assistant', 'id'),
                'label_en' => self::translate('ui.navigation.assistant', 'en'),
                'description_id' => 'Group menu untuk pengaturan dan knowledge AI Agent.',
                'description_en' => 'Menu group for AI Agent settings and knowledge.',
                'sort' => 20,
                'icon' => 'heroicon-o-sparkles',
            ],
            'master_data' => [
                'label_id' => self::translate('ui.navigation.master_data', 'id'),
                'label_en' => self::translate('ui.navigation.master_data', 'en'),
                'description_id' => 'Group menu untuk data master customer dan WhatsApp.',
                'description_en' => 'Menu group for customer and WhatsApp master data.',
                'sort' => 30,
                'icon' => 'heroicon-o-circle-stack',
            ],
            'monitoring' => [
                'label_id' => self::translate('ui.navigation.monitoring', 'id'),
                'label_en' => self::translate('ui.navigation.monitoring', 'en'),
                'description_id' => 'Group menu untuk monitoring log dan proses sistem.',
                'description_en' => 'Menu group for monitoring logs and system processes.',
                'sort' => 40,
                'icon' => 'heroicon-o-clipboard-document-list',
            ],
            'settings' => [
                'label_id' => self::translate('ui.navigation.settings', 'id'),
                'label_en' => self::translate('ui.navigation.settings', 'en'),
                'description_id' => 'Group menu untuk pengaturan aplikasi dan akses user.',
                'description_en' => 'Menu group for application settings and user access.',
                'sort' => 50,
                'icon' => 'heroicon-o-cog-6-tooth',
            ],
        ];
    }

    /**
     * @return array<string, array{group: string|null, sort: int, icon: string|null, label_id: string, label_en: string, description_id: string|null, description_en: string|null}>
     */
    public static function sidebarMenus(): array
    {
        $definitions = self::localizedDefinitions();

        return [
            self::DASHBOARD_VIEW => self::sidebarPermissionMenu($definitions, self::DASHBOARD_VIEW, null, 1, 'heroicon-o-home'),
            self::INBOX_VIEW => self::sidebarPermissionMenu($definitions, self::INBOX_VIEW, 'operasional', 10, 'heroicon-o-chat-bubble-left-right'),
            self::TICKET_VIEW => self::sidebarPermissionMenu($definitions, self::TICKET_VIEW, 'operasional', 20, 'heroicon-o-ticket'),
            self::AI_AGENT_VIEW => self::sidebarPermissionMenu($definitions, self::AI_AGENT_VIEW, 'assistant', 10, 'heroicon-o-sparkles'),
            self::KNOWLEDGE_VIEW => self::sidebarPermissionMenu($definitions, self::KNOWLEDGE_VIEW, 'assistant', 20, 'heroicon-o-book-open'),
            self::MASTER_CUSTOMER_VIEW => self::sidebarPermissionMenu($definitions, self::MASTER_CUSTOMER_VIEW, 'master_data', 10, 'heroicon-o-squares-2x2'),
            self::MENU_MASTER_INSTANSI => self::sidebarMenu('master_data', 20, 'heroicon-o-building-office-2', 'ui.models.instansi.label', 'ui.models.instansi.plural'),
            self::MENU_MASTER_CUSTOMER => self::sidebarMenu('master_data', 30, 'heroicon-o-user-group', 'ui.models.customer.label', 'ui.models.customer.plural'),
            self::MENU_MASTER_NOMOR_WHATSAPP => self::sidebarMenu('master_data', 40, 'heroicon-o-device-phone-mobile', 'ui.models.nomor_whatsapp.label', 'ui.models.nomor_whatsapp.plural'),
            self::MENU_MASTER_GRUP_WHATSAPP => self::sidebarMenu('master_data', 50, 'heroicon-o-chat-bubble-left-right', 'ui.models.grup_whatsapp.label', 'ui.models.grup_whatsapp.plural'),
            self::MENU_MASTER_ANGGOTA_GRUP => self::sidebarMenu('master_data', 60, 'heroicon-o-users', 'ui.models.anggota_grup.label', 'ui.models.anggota_grup.plural'),
            self::HOLIDAY_VIEW => self::sidebarPermissionMenu($definitions, self::HOLIDAY_VIEW, 'master_data', 70, 'heroicon-o-calendar-days'),
            self::LOG_DATA_VIEW => self::sidebarPermissionMenu($definitions, self::LOG_DATA_VIEW, 'monitoring', 10, 'heroicon-o-clipboard-document-list'),
            self::HAK_AKSES_VIEW => self::sidebarPermissionMenu($definitions, self::HAK_AKSES_VIEW, 'settings', 10, 'heroicon-o-shield-check'),
            self::USER_VIEW => self::sidebarPermissionMenu($definitions, self::USER_VIEW, 'settings', 20, 'heroicon-o-user-group'),
            self::JOB_SCHEDULE_VIEW => self::sidebarPermissionMenu($definitions, self::JOB_SCHEDULE_VIEW, 'settings', 30, 'heroicon-o-clock'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function permissionSidebarGroups(): array
    {
        return [
            self::INBOX_REPLY => 'operasional',
            self::INBOX_MANAGE => 'operasional',
            self::TICKET_MANAGE => 'operasional',
            self::AI_AGENT_MANAGE => 'assistant',
            self::MASTER_CUSTOMER_MANAGE => 'master_data',
            self::KNOWLEDGE_MANAGE => 'assistant',
            self::HOLIDAY_MANAGE => 'master_data',
            self::USER_MANAGE => 'settings',
            self::HAK_AKSES_MANAGE => 'settings',
        ];
    }

    /**
     * @param  array<string, array{label_id: string, label_en: string, module_id: string, module_en: string, description_id: string, description_en: string}>  $definitions
     * @return array{group: string|null, sort: int, icon: string|null, label_id: string, label_en: string, description_id: string|null, description_en: string|null}
     */
    private static function sidebarPermissionMenu(array $definitions, string $code, ?string $group, int $sort, ?string $icon): array
    {
        $definition = $definitions[$code];

        return [
            'group' => $group,
            'sort' => $sort,
            'icon' => $icon,
            'label_id' => $definition['label_id'],
            'label_en' => $definition['label_en'],
            'description_id' => $definition['description_id'],
            'description_en' => $definition['description_en'],
        ];
    }

    /**
     * @return array{group: string|null, sort: int, icon: string|null, label_id: string, label_en: string, description_id: string|null, description_en: string|null}
     */
    private static function sidebarMenu(?string $group, int $sort, ?string $icon, string $labelKey, string $descriptionKey): array
    {
        return [
            'group' => $group,
            'sort' => $sort,
            'icon' => $icon,
            'label_id' => self::translate($labelKey, 'id'),
            'label_en' => self::translate($labelKey, 'en'),
            'description_id' => self::translate($descriptionKey, 'id'),
            'description_en' => self::translate($descriptionKey, 'en'),
        ];
    }

    /**
     * @return array<string, array{label: string, module: string, description: string}>
     */
    private static function mapDefinitions(string $locale): array
    {
        $definitions = [];

        foreach (self::definitionKeys() as $code => $keys) {
            $definitions[$code] = [
                'label' => self::translate($keys['label'], $locale),
                'module' => self::translate($keys['module'], $locale),
                'description' => self::translate($keys['description'], $locale),
            ];
        }

        return $definitions;
    }

    private static function translate(string $key, string $locale): string
    {
        return __($key, [], $locale);
    }

    /**
     * @return array<string, array{label: string, module: string, description: string}>
     */
    private static function definitionKeys(): array
    {
        return [
            self::DASHBOARD_VIEW => [
                'label' => 'ui.permissions.dashboard_view',
                'module' => 'ui.permissions.dashboard_module',
                'description' => 'ui.permissions.dashboard_view_desc',
            ],
            self::INBOX_VIEW => [
                'label' => 'ui.permissions.inbox_view',
                'module' => 'ui.permissions.inbox_module',
                'description' => 'ui.permissions.inbox_view_desc',
            ],
            self::INBOX_REPLY => [
                'label' => 'ui.permissions.inbox_reply',
                'module' => 'ui.permissions.inbox_module',
                'description' => 'ui.permissions.inbox_reply_desc',
            ],
            self::INBOX_MANAGE => [
                'label' => 'ui.permissions.inbox_manage',
                'module' => 'ui.permissions.inbox_module',
                'description' => 'ui.permissions.inbox_manage_desc',
            ],
            self::TICKET_VIEW => [
                'label' => 'ui.permissions.ticket_view',
                'module' => 'ui.permissions.ticket_module',
                'description' => 'ui.permissions.ticket_view_desc',
            ],
            self::TICKET_MANAGE => [
                'label' => 'ui.permissions.ticket_manage',
                'module' => 'ui.permissions.ticket_module',
                'description' => 'ui.permissions.ticket_manage_desc',
            ],
            self::AI_AGENT_VIEW => [
                'label' => 'ui.permissions.ai_agent_view',
                'module' => 'ui.permissions.ai_agent_module',
                'description' => 'ui.permissions.ai_agent_view_desc',
            ],
            self::AI_AGENT_MANAGE => [
                'label' => 'ui.permissions.ai_agent_manage',
                'module' => 'ui.permissions.ai_agent_module',
                'description' => 'ui.permissions.ai_agent_manage_desc',
            ],
            self::LOG_DATA_VIEW => [
                'label' => 'ui.permissions.log_data_view',
                'module' => 'ui.permissions.monitoring_module',
                'description' => 'ui.permissions.log_data_view_desc',
            ],
            self::MASTER_CUSTOMER_VIEW => [
                'label' => 'ui.permissions.master_customer_view',
                'module' => 'ui.permissions.master_customer_module',
                'description' => 'ui.permissions.master_customer_view_desc',
            ],
            self::MASTER_CUSTOMER_MANAGE => [
                'label' => 'ui.permissions.master_customer_manage',
                'module' => 'ui.permissions.master_customer_module',
                'description' => 'ui.permissions.master_customer_manage_desc',
            ],
            self::KNOWLEDGE_VIEW => [
                'label' => 'ui.permissions.knowledge_view',
                'module' => 'ui.permissions.knowledge_module',
                'description' => 'ui.permissions.knowledge_view_desc',
            ],
            self::KNOWLEDGE_MANAGE => [
                'label' => 'ui.permissions.knowledge_manage',
                'module' => 'ui.permissions.knowledge_module',
                'description' => 'ui.permissions.knowledge_manage_desc',
            ],
            self::HOLIDAY_VIEW => [
                'label' => 'ui.permissions.holiday_view',
                'module' => 'ui.permissions.holiday_module',
                'description' => 'ui.permissions.holiday_view_desc',
            ],
            self::HOLIDAY_MANAGE => [
                'label' => 'ui.permissions.holiday_manage',
                'module' => 'ui.permissions.holiday_module',
                'description' => 'ui.permissions.holiday_manage_desc',
            ],
            self::USER_VIEW => [
                'label' => 'ui.permissions.user_view',
                'module' => 'ui.permissions.user_module',
                'description' => 'ui.permissions.user_view_desc',
            ],
            self::USER_MANAGE => [
                'label' => 'ui.permissions.user_manage',
                'module' => 'ui.permissions.user_module',
                'description' => 'ui.permissions.user_manage_desc',
            ],
            self::CHAT_HISTORY_VIEW => [
                'label' => 'ui.permissions.chat_history_view',
                'module' => 'ui.permissions.chat_history_module',
                'description' => 'ui.permissions.chat_history_view_desc',
            ],
            self::HAK_AKSES_VIEW => [
                'label' => 'ui.permissions.hak_akses_view',
                'module' => 'ui.permissions.settings_module',
                'description' => 'ui.permissions.hak_akses_view_desc',
            ],
            self::HAK_AKSES_MANAGE => [
                'label' => 'ui.permissions.hak_akses_manage',
                'module' => 'ui.permissions.settings_module',
                'description' => 'ui.permissions.hak_akses_manage_desc',
            ],
            self::JOB_SCHEDULE_VIEW => [
                'label' => 'ui.permissions.job_schedule_view',
                'module' => 'ui.permissions.settings_module',
                'description' => 'ui.permissions.job_schedule_view_desc',
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
                self::HAK_AKSES_VIEW,
                self::HAK_AKSES_MANAGE,
                self::JOB_SCHEDULE_VIEW,
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
                'name' => __('ui.roles.admin'),
                'description' => __('ui.roles.admin_desc'),
            ],
            'SUPERVISOR_CS' => [
                'name' => __('ui.roles.supervisor_cs'),
                'description' => __('ui.roles.supervisor_cs_desc'),
            ],
            'CS' => [
                'name' => __('ui.roles.cs'),
                'description' => __('ui.roles.cs_desc'),
            ],
            'DEVELOPER' => [
                'name' => __('ui.roles.developer'),
                'description' => __('ui.roles.developer_desc'),
            ],
            'VIEWER' => [
                'name' => __('ui.roles.viewer'),
                'description' => __('ui.roles.viewer_desc'),
            ],
        ];
    }
}
