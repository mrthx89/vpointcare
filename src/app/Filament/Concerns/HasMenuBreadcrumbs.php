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
        return FilamentBreadcrumbs::forMenu(
            static::$breadcrumbMenuCode,
            static::getResource()::getNavigationLabel()
        );
    }
}
