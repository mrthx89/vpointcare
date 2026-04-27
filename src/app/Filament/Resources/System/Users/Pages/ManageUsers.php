<?php

namespace App\Filament\Resources\System\Users\Pages;

use App\Filament\Resources\System\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    if (($data['status'] ?? User::STATUS_PENDING) === User::STATUS_APPROVED) {
                        $data['approved_at'] = now();
                    }

                    return $data;
                }),
        ];
    }
}
