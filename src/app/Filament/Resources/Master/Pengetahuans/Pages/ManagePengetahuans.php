<?php

namespace App\Filament\Resources\Master\Pengetahuans\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Master\Pengetahuans\PengetahuanResource;
use App\Support\AccessPermissions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePengetahuans extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = PengetahuanResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::KNOWLEDGE_VIEW;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => PengetahuanResource::canCreate()),
        ];
    }
}
