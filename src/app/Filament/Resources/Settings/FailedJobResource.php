<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\FailedJobResource\Pages;
use App\Models\FailedJob;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\NavigationHelper;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use UnitEnum;

class FailedJobResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    public static function getModel(): string
    {
        return FailedJob::class;
    }

    public static function getNavigationIcon(): string | BackedEnum | \Illuminate\Contracts\Support\Htmlable | null
    {
        return NavigationHelper::iconFor(AccessPermissions::QUEUE_MONITOR_VIEW, 'heroicon-o-exclamation-triangle');
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationHelper::groupFor(AccessPermissions::QUEUE_MONITOR_VIEW, __('ui.navigation.settings'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::QUEUE_MONITOR_VIEW, 35);
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::QUEUE_MONITOR_VIEW, __('ui.queue_monitor.label'));
    }

    public static function getModelLabel(): string
    {
        return __('ui.queue_monitor.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.queue_monitor.plural');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::QUEUE_MONITOR_VIEW)
            && NavigationHelper::isActive(AccessPermissions::QUEUE_MONITOR_VIEW);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label(__('ui.queue_monitor.uuid')),
                TextInput::make('queue')
                    ->label(__('ui.queue_monitor.queue')),
                TextInput::make('failed_at')
                    ->label(__('ui.queue_monitor.failed_at')),
                Textarea::make('payload')
                    ->label(__('ui.queue_monitor.payload'))
                    ->columnSpanFull()
                    ->rows(6),
                Textarea::make('exception')
                    ->label(__('ui.queue_monitor.exception'))
                    ->columnSpanFull()
                    ->rows(6),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('queue')
                    ->label(__('ui.queue_monitor.queue'))
                    ->sortable()
                    ->badge(),
                TextColumn::make('failed_at')
                    ->label(__('ui.queue_monitor.failed_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('exception')
                    ->label(__('ui.queue_monitor.exception'))
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->exception)
                    ->color('danger'),
            ])
            ->actions([
                Action::make('retry')
                    ->label(__('ui.queue_monitor.retry'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function (FailedJob $record) {
                        try {
                            Artisan::call('queue:retry', ['id' => [$record->uuid]]);
                            Notification::make()
                                ->title(__('ui.queue_monitor.retry_success'))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Retry Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                BulkAction::make('retry_bulk')
                    ->label(__('ui.queue_monitor.retry'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function (Collection $records) {
                        $uuids = $records->pluck('uuid')->toArray();
                        try {
                            Artisan::call('queue:retry', ['id' => $uuids]);
                            Notification::make()
                                ->title(__('ui.queue_monitor.retry_success'))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Retry Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFailedJobs::route('/'),
        ];
    }
}
