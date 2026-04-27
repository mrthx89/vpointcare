<?php

namespace App\Filament\Resources\Master\Pengetahuans\Pages;

use App\Filament\Resources\Master\Pengetahuans\PengetahuanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePengetahuans extends ManageRecords
{
    protected static string $resource = PengetahuanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
