<?php

namespace App\Filament\Resources\Ai\DraftPengetahuans\Pages;

use App\Filament\Resources\Ai\DraftPengetahuans\DraftPengetahuanResource;
use App\Support\AccessPermissions;
use App\Support\FilamentBreadcrumbs;
use Filament\Resources\Pages\ManageRecords;

class ManageDraftPengetahuans extends ManageRecords
{
    protected static string $resource = DraftPengetahuanResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::KNOWLEDGE_VIEW;

    public function getBreadcrumbs(): array
    {
        return FilamentBreadcrumbs::forMenu(static::$breadcrumbMenuCode, __('ui.ai_learning.draft_knowledge_ai'));
    }
}
