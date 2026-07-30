<?php

namespace App\Filament\Concerns;

use App\Support\FilamentBreadcrumbs;

trait HasMenuBreadcrumbs
{
    public function getBreadcrumb(): ?string
    {
        return static::getResource()::getNavigationLabel();
    }

    /**
     * @return array<int, string>
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = FilamentBreadcrumbs::forMenu(
            static::$breadcrumbMenuCode,
            static::getResource()::getNavigationLabel()
        );

        $resourceLabel = static::getResource()::getNavigationLabel();

        if (($breadcrumbs[array_key_last($breadcrumbs)] ?? null) !== $resourceLabel) {
            $breadcrumbs[] = $resourceLabel;
        }

        return $breadcrumbs;
    }
}
