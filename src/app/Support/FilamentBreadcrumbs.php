<?php

namespace App\Support;

class FilamentBreadcrumbs
{
    /**
     * @return array<int, string>
     */
    public static function forMenu(string $menuCode, ?string $fallbackLabel = null): array
    {
        $menu = NavigationHelper::labelFor($menuCode, $fallbackLabel ?? $menuCode);
        $group = NavigationHelper::groupLabelForMenu($menuCode);

        if ($menuCode === AccessPermissions::DASHBOARD_VIEW) {
            return self::fromGroupAndMenu(null, $menu);
        }

        return self::fromGroupAndMenu($group, $menu);
    }

    /**
     * @return array<int, string>
     */
    public static function fromGroupAndMenu(?string $group, string $menu): array
    {
        if (blank($group)) {
            return [$menu];
        }

        return [
            $group,
            $menu,
        ];
    }
}
