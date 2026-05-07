<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\JobScheduleResource\Pages;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\NavigationHelper;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class JobScheduleResource extends Resource
{
    protected static ?string $model = \App\Models\JobSchedule::class;

    public static function getModel(): string
    {
        return \App\Models\JobSchedule::class;
    }

    public static function getNavigationIcon(): string | BackedEnum | \Illuminate\Contracts\Support\Htmlable | null
    {
        return NavigationHelper::iconFor(AccessPermissions::JOB_SCHEDULE_VIEW, 'heroicon-o-clock');
    }
    
    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationHelper::groupFor(AccessPermissions::JOB_SCHEDULE_VIEW, __('ui.navigation.settings'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::JOB_SCHEDULE_VIEW, 30);
    }
    
    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::JOB_SCHEDULE_VIEW, __('ui.models.job_schedule.label'));
    }

    public static function getModelLabel(): string
    {
        return __('ui.models.job_schedule.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.models.job_schedule.plural');
    }
    
    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::JOB_SCHEDULE_VIEW)
            && NavigationHelper::isActive(AccessPermissions::JOB_SCHEDULE_VIEW);
    }

    public static function canCreate(): bool
    {
        return false; // User tidak bisa menambah job baru
    }

    public static function canEdit($record): bool
    {
        return FilamentAccess::can(AccessPermissions::JOB_SCHEDULE_VIEW);
    }

    public static function canDelete($record): bool
    {
        return false; // User tidak bisa menghapus
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('ui.models.job_schedule.name'))
                    ->disabled(),
                TextInput::make('command')
                    ->label(__('ui.models.job_schedule.command'))
                    ->disabled(),
                Select::make('cron_expression')
                    ->label(__('ui.models.job_schedule.cron_expression'))
                    ->options([
                        'everySecond' => __('ui.models.job_schedule.everySecond'),
                        'everyTwoSeconds' => __('ui.models.job_schedule.everyTwoSeconds'),
                        'everyFiveSeconds' => __('ui.models.job_schedule.everyFiveSeconds'),
                        'everyTenSeconds' => __('ui.models.job_schedule.everyTenSeconds'),
                        'everyFifteenSeconds' => __('ui.models.job_schedule.everyFifteenSeconds'),
                        'everyTwentySeconds' => __('ui.models.job_schedule.everyTwentySeconds'),
                        'everyThirtySeconds' => __('ui.models.job_schedule.everyThirtySeconds'),
                        'everyMinute' => __('ui.models.job_schedule.everyMinute'),
                        'everyFiveMinutes' => __('ui.models.job_schedule.everyFiveMinutes'),
                        'everyTenMinutes' => __('ui.models.job_schedule.everyTenMinutes'),
                        'hourly' => __('ui.models.job_schedule.hourly'),
                        'daily' => __('ui.models.job_schedule.daily'),
                    ])
                    ->required()
                    ->default('everyMinute'),
                Toggle::make('is_active')
                    ->label(__('ui.models.job_schedule.is_active'))
                    ->default(true),
                Textarea::make('description')
                    ->label(__('ui.models.job_schedule.description'))
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('ui.models.job_schedule.name')),
                TextColumn::make('command')
                    ->label(__('ui.models.job_schedule.command'))
                    ->color('gray')
                    ->size('sm'),
                TextColumn::make('cron_expression')
                    ->label(__('ui.models.job_schedule.cron_expression'))
                    ->formatStateUsing(function (string $state) {
                        $labels = [
                            'everySecond' => __('ui.models.job_schedule.everySecond'),
                            'everyTwoSeconds' => __('ui.models.job_schedule.everyTwoSeconds'),
                            'everyFiveSeconds' => __('ui.models.job_schedule.everyFiveSeconds'),
                            'everyTenSeconds' => __('ui.models.job_schedule.everyTenSeconds'),
                            'everyFifteenSeconds' => __('ui.models.job_schedule.everyFifteenSeconds'),
                            'everyTwentySeconds' => __('ui.models.job_schedule.everyTwentySeconds'),
                            'everyThirtySeconds' => __('ui.models.job_schedule.everyThirtySeconds'),
                            'everyMinute' => __('ui.models.job_schedule.everyMinute'),
                            'everyFiveMinutes' => __('ui.models.job_schedule.everyFiveMinutes'),
                            'everyTenMinutes' => __('ui.models.job_schedule.everyTenMinutes'),
                            'hourly' => __('ui.models.job_schedule.hourly'),
                            'daily' => __('ui.models.job_schedule.daily'),
                        ];
                        return $labels[$state] ?? $state;
                    })
                    ->badge()
                    ->color('info'),
                ToggleColumn::make('is_active')
                    ->label(__('ui.models.job_schedule.is_active')),
            ])
            ->paginated(false)
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageJobSchedules::route('/'),
        ];
    }
}
