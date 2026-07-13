<?php

namespace App\Filament\Resources\Ticketing\StatusTasks;

use App\Filament\Resources\Ticketing\StatusTasks\Pages\ManageStatusTasks;
use App\Models\Ticketing\StatusTask;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\NavigationHelper;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class StatusTaskResource extends Resource
{
    protected static ?string $model = StatusTask::class;

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::TASK_VIEW, __('ui.navigation.operasional'));
    }

    public static function getNavigationLabel(): string
    {
        return __('ui.ticketing.status_task');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::TASK_MANAGE);
    }

    public static function form(Schema $s): Schema
    {
        return $s->components([TextInput::make('KodeStatusTask')->required(), TextInput::make('NamaStatusTask')->required(), TextInput::make('Urutan')->numeric(), TextInput::make('Warna'), Toggle::make('StatusFinal'), Toggle::make('NonAktif')]);
    }

    public static function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('KodeStatusTask'), TextColumn::make('NamaStatusTask'), TextColumn::make('Urutan'), ToggleColumn::make('StatusFinal'), ToggleColumn::make('NonAktif')])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageStatusTasks::route('/')];
    }
}
