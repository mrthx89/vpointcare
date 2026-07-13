<?php

namespace App\Filament\Resources\Ticketing\StatusTickets;

use App\Filament\Resources\Ticketing\StatusTickets\Pages\ManageStatusTickets;
use App\Models\Ticketing\StatusTicket;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class StatusTicketResource extends Resource
{
    protected static ?string $model = StatusTicket::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Ticketing';

    public static function getNavigationLabel(): string
    {
        return __('ui.ticketing.status_ticket');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::TICKET_MANAGE);
    }

    public static function form(Schema $s): Schema
    {
        return $s->components([TextInput::make('KodeStatusTicket')->required(), TextInput::make('NamaStatusTicket')->required(), TextInput::make('Urutan')->numeric(), TextInput::make('Warna'), Toggle::make('StatusFinal'), Toggle::make('NonAktif')]);
    }

    public static function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('KodeStatusTicket'), TextColumn::make('NamaStatusTicket'), TextColumn::make('Urutan'), ToggleColumn::make('StatusFinal'), ToggleColumn::make('NonAktif')])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageStatusTickets::route('/')];
    }
}
