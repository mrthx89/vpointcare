<?php

namespace App\Filament\Resources\Ticketing\Prioritas;

use App\Filament\Resources\Ticketing\Prioritas\Pages\ManagePrioritasTickets;
use App\Models\Ticketing\PrioritasTicket;
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

class PrioritasTicketResource extends Resource
{
    protected static ?string $model = PrioritasTicket::class;

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::TICKET_VIEW, __('ui.navigation.operasional'));
    }

    public static function getNavigationLabel(): string
    {
        return __('ui.ticketing.priority');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::TICKET_MANAGE);
    }

    public static function form(Schema $s): Schema
    {
        return $s->components([TextInput::make('KodePrioritas')->required(), TextInput::make('NamaPrioritas')->required(), TextInput::make('Urutan')->numeric(), TextInput::make('BatasSlaMenit')->numeric(), TextInput::make('Warna'), Toggle::make('NonAktif')]);
    }

    public static function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('KodePrioritas'), TextColumn::make('NamaPrioritas'), TextColumn::make('BatasSlaMenit'), ToggleColumn::make('NonAktif')])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePrioritasTickets::route('/')];
    }
}
