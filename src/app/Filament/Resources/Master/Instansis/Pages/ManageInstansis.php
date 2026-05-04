<?php

namespace App\Filament\Resources\Master\Instansis\Pages;

use App\Filament\Resources\Master\Instansis\InstansiResource;
use App\Jobs\ImportVTokenCustomersToInstansi;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageInstansis extends ManageRecords
{
    protected static string $resource = InstansiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncVToken')
                ->label('Syncron Data')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Syncron Data Instansi')
                ->modalDescription('Data customer VToken akan diambil dan disinkronkan ke MInstansi berdasarkan kode.')
                ->action(function (): void {
                    if (blank(config('services.vtoken.open_customers_url'))) {
                        Notification::make()
                            ->title('URL sinkron belum diatur')
                            ->body('Isi VTOKEN_OPEN_CUSTOMERS_URL di file .env terlebih dahulu.')
                            ->danger()
                            ->send();

                        return;
                    }

                    ImportVTokenCustomersToInstansi::dispatch();

                    Notification::make()
                        ->title('Job syncron data sudah masuk queue')
                        ->body('Jalankan queue worker agar proses import berjalan.')
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
