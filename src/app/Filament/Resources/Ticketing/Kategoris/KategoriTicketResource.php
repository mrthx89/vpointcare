<?php

namespace App\Filament\Resources\Ticketing\Kategoris;

use App\Filament\Resources\Ticketing\Kategoris\Pages\ManageKategoriTickets;
use App\Models\Ticketing\KategoriTicket;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class KategoriTicketResource extends Resource
{
    protected static ?string $model = KategoriTicket::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Ticketing';

    public static function getNavigationLabel(): string
    {
        return __('ui.ticketing.category');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::TICKET_MANAGE);
    }

    public static function form(Schema $s): Schema
    {
        return $s->components([TextInput::make('KodeKategori')->required(), TextInput::make('NamaKategori')->required(), Textarea::make('Keterangan'), Toggle::make('NonAktif')]);
    }

    public static function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('KodeKategori'), TextColumn::make('NamaKategori'), ToggleColumn::make('NonAktif')])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageKategoriTickets::route('/')];
    }
}
