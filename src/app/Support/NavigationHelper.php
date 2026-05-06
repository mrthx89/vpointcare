<?php

namespace App\Support;

use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NavigationHelper
{
    public const CACHE_KEY = 'wacs_navigation_data';

    public const CACHE_TTL = 3600;

    public static function labelFor(string $menuCode, ?string $fallback = null): string
    {
        $row = self::menu($menuCode);

        return $row['label'] ?? $fallback ?? $menuCode;
    }

    public static function groupFor(string $menuCode, ?string $fallback = null): ?string
    {
        $row = self::menu($menuCode);

        if (array_key_exists('group_key', $row)) {
            return $row['group_key'];
        }

        return $fallback;
    }

    public static function sortFor(string $menuCode, ?int $fallback = null): ?int
    {
        $row = self::menu($menuCode);

        return $row['sort'] ?? $fallback ?? self::fallbackMenu($menuCode)['sort'] ?? null;
    }

    public static function iconFor(string $menuCode, string | \BackedEnum | null $fallback = null): string | \BackedEnum | null
    {
        $row = self::menu($menuCode);

        return $row['icon'] ?? $fallback ?? self::fallbackMenu($menuCode)['icon'] ?? null;
    }

    public static function isActive(string $menuCode): bool
    {
        $row = self::menu($menuCode);

        return ! ($row['inactive'] ?? false);
    }

    /**
     * @return array<string, NavigationGroup>
     */
    public static function buildGroups(): array
    {
        $groups = self::data()['groups'];

        if ($groups === []) {
            $groups = self::fallbackGroups();
        }

        usort($groups, fn (array $a, array $b): int => ($a['sort'] ?? 9999) <=> ($b['sort'] ?? 9999));

        $navigationGroups = [];

        foreach ($groups as $group) {
            $key = (string) ($group['key'] ?? $group['label']);

            $navigationGroups[$key] = NavigationGroup::make(fn (): string => self::groupLabelFor($key, (string) $group['label']))
                ->icon(fn (): string | \BackedEnum | null => self::groupIconFor($key, $group['icon'] ?? null));
        }

        return $navigationGroups;
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);

        foreach (array_keys(LocaleManager::supported()) as $locale) {
            Cache::forget(self::CACHE_KEY . ':' . $locale);
        }
    }

    /**
     * @return array{menus: array<string, array<string, mixed>>, groups: array<int, array<string, mixed>>}
     */
    private static function data(): array
    {
        return Cache::remember(self::CACHE_KEY . ':' . LocaleManager::current(), self::CACHE_TTL, function (): array {
            try {
                if (
                    ! Schema::hasTable('MHakAkses')
                    || ! Schema::hasColumn('MHakAkses', 'IdHakAkses')
                    || ! Schema::hasColumn('MHakAkses', 'SortOrder')
                    || ! Schema::hasColumn('MHakAkses', 'IconString')
                ) {
                    return [
                        'menus' => self::fallbackMenus(),
                        'groups' => self::fallbackGroups(),
                    ];
                }

                $columns = AccessPermissions::localizedColumnNames();

                $rows = DB::table('MHakAkses as h')
                    ->leftJoin('MHakAkses as p', 'p.Id', '=', 'h.IdHakAkses')
                    ->select([
                        'h.KodeHakAkses',
                        'h.Id',
                        'h.IdHakAkses',
                        'h.SortOrder',
                        'h.IconString',
                        'h.NonAktif',
                        DB::raw('p.Id as GroupId'),
                        DB::raw("h.{$columns['label']} as LabelMenu"),
                        DB::raw("p.{$columns['label']} as LabelGroup"),
                        DB::raw('p.SortOrder as SortGroup'),
                        DB::raw('p.NonAktif as GroupNonAktif'),
                    ])
                    ->get();

                $menus = self::fallbackMenus();
                $groups = [];

                foreach ($rows as $row) {
                    $isGroup = $row->KodeHakAkses === null && $row->IdHakAkses === null;

                    if ($isGroup && ! (bool) $row->NonAktif) {
                        $groups[] = [
                            'key' => (string) $row->Id,
                            'label' => (string) $row->LabelMenu,
                            'sort' => $row->SortOrder !== null ? (int) $row->SortOrder : 9999,
                            'icon' => $row->IconString ?: null,
                        ];

                        continue;
                    }

                    if ($row->KodeHakAkses === null) {
                        continue;
                    }

                    $menus[(string) $row->KodeHakAkses] = [
                        'label' => (string) ($row->LabelMenu ?: ($menus[(string) $row->KodeHakAkses]['label'] ?? $row->KodeHakAkses)),
                        'group_key' => $row->GroupId !== null && ! (bool) $row->GroupNonAktif ? (string) $row->GroupId : null,
                        'group_label' => $row->LabelGroup !== null && ! (bool) $row->GroupNonAktif ? (string) $row->LabelGroup : null,
                        'sort' => $row->SortOrder !== null ? (int) $row->SortOrder : ($menus[(string) $row->KodeHakAkses]['sort'] ?? null),
                        'icon' => $row->IconString ?: ($menus[(string) $row->KodeHakAkses]['icon'] ?? null),
                        'inactive' => (bool) $row->NonAktif,
                    ];
                }

                return [
                    'menus' => $menus,
                    'groups' => $groups !== [] ? $groups : self::fallbackGroups(),
                ];
            } catch (\Throwable) {
                return [
                    'menus' => self::fallbackMenus(),
                    'groups' => self::fallbackGroups(),
                ];
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private static function menu(string $menuCode): array
    {
        return self::data()['menus'][$menuCode] ?? self::fallbackMenu($menuCode);
    }

    /**
     * @return array<string, mixed>
     */
    private static function fallbackMenu(string $menuCode): array
    {
        return self::fallbackMenus()[$menuCode] ?? [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function fallbackMenus(): array
    {
        $menus = [];

        foreach (AccessPermissions::sidebarMenus() as $code => $menu) {
            $menus[$code] = [
                'label' => LocaleManager::current() === 'en' ? $menu['label_en'] : $menu['label_id'],
                'group_key' => $menu['group'],
                'group_label' => $menu['group'] ? self::fallbackGroupsByKey()[$menu['group']]['label'] ?? null : null,
                'sort' => $menu['sort'],
                'icon' => $menu['icon'],
                'inactive' => false,
            ];
        }

        return $menus;
    }

    /**
     * @return array<int, array{key: string, label: string, sort: int, icon: string|null}>
     */
    private static function fallbackGroups(): array
    {
        return array_values(self::fallbackGroupsByKey());
    }

    /**
     * @return array<string, array{key: string, label: string, sort: int, icon: string|null}>
     */
    private static function fallbackGroupsByKey(): array
    {
        $groups = [];

        foreach (AccessPermissions::sidebarGroups() as $key => $group) {
            $groups[$key] = [
                'key' => $key,
                'label' => LocaleManager::current() === 'en' ? $group['label_en'] : $group['label_id'],
                'sort' => $group['sort'],
                'icon' => $group['icon'],
            ];
        }

        return $groups;
    }

    private static function groupLabelFor(string $key, string $fallback): string
    {
        foreach (self::data()['groups'] as $group) {
            if ((string) ($group['key'] ?? '') === $key) {
                return (string) $group['label'];
            }
        }

        return $fallback;
    }

    private static function groupIconFor(string $key, string | \BackedEnum | null $fallback = null): string | \BackedEnum | null
    {
        foreach (self::data()['groups'] as $group) {
            if ((string) ($group['key'] ?? '') === $key) {
                return $group['icon'] ?? $fallback;
            }
        }

        return $fallback;
    }
}
