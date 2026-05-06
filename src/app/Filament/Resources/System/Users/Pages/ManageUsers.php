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
                ->visible(fn (): bool => UserResource::canCreate())
                ->using(function (array $data): User {
                    abort_unless(UserResource::canCreate(), 403);

                    [$userData, $profileData] = UserResource::splitFormData($data);

                    $record = User::query()->create(UserResource::normalizeUserData($userData));
                    UserResource::syncProfile($record, $profileData);

                    return $record;
                }),
        ];
    }
}
