<?php

namespace App\Filament\Resources\Master\HakAkseses\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Master\HakAkseses\HakAksesResource;
use App\Support\AccessPermissions;
use Filament\Resources\Pages\ManageRecords;

class ManageHakAkseses extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = HakAksesResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::HAK_AKSES_VIEW;
}
