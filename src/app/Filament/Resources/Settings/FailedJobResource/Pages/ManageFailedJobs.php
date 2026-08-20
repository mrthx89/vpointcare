<?php

namespace App\Filament\Resources\Settings\FailedJobResource\Pages;

use App\Filament\Concerns\HasMenuBreadcrumbs;
use App\Filament\Resources\Settings\FailedJobResource;
use App\Support\AccessPermissions;
use App\Models\FailedJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Artisan;

class ManageFailedJobs extends ManageRecords
{
    use HasMenuBreadcrumbs;

    protected static string $resource = FailedJobResource::class;

    protected static string $breadcrumbMenuCode = AccessPermissions::QUEUE_MONITOR_VIEW;

    public function getSubheading(): ?string
    {
        return __('ui.queue_monitor.subtitle');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retry_all')
                ->label(__('ui.queue_monitor.retry_all'))
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('ui.queue_monitor.confirm_retry_all_heading'))
                ->modalDescription(__('ui.queue_monitor.confirm_retry_all_description'))
                ->modalSubmitActionLabel(__('ui.queue_monitor.retry_all'))
                ->modalCancelActionLabel(__('ui.common.cancel'))
                ->action(function () {
                    try {
                        Artisan::call('queue:retry', ['id' => ['all']]);
                        Notification::make()
                            ->title(__('ui.queue_monitor.retry_all_success'))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title(__('ui.queue_monitor.retry_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => FailedJob::query()->exists()),

            Action::make('flush_all')
                ->label(__('ui.queue_monitor.flush_all'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('ui.queue_monitor.confirm_flush_all_heading'))
                ->modalDescription(__('ui.queue_monitor.confirm_flush_all_description'))
                ->modalSubmitActionLabel(__('ui.queue_monitor.flush_all'))
                ->modalCancelActionLabel(__('ui.common.cancel'))
                ->action(function () {
                    try {
                        Artisan::call('queue:flush');
                        Notification::make()
                            ->title(__('ui.queue_monitor.flush_all_success'))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title(__('ui.queue_monitor.retry_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => FailedJob::query()->exists()),
        ];
    }
}
