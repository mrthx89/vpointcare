<?php

namespace App\Filament\Resources\Master\AnggotaGrupWhatsapps;

use App\Filament\Resources\Master\AnggotaGrupWhatsapps\Pages\ManageAnggotaGrupWhatsapps;
use App\Models\Master\AnggotaGrupWhatsapp;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class AnggotaGrupWhatsappResource extends Resource
{
    protected static ?string $model = AnggotaGrupWhatsapp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Anggota Grup';

    protected static ?string $modelLabel = 'Anggota Grup';

    protected static ?string $pluralModelLabel = 'Anggota Grup';

    protected static ?int $navigationSort = 45;

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_VIEW);
    }

    public static function canCreate(): bool
    {
        return FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_MANAGE);
    }

    public static function canEdit($record): bool
    {
        return FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_MANAGE);
    }

    public static function canDelete($record): bool
    {
        return FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_MANAGE);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('IdGrupWhatsapp')
                    ->label('Grup WhatsApp')
                    ->relationship('grupWhatsapp', 'NamaGrup')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('IdNomorWhatsapp')
                    ->label('Nomor WhatsApp')
                    ->relationship('nomorWhatsapp', 'NomorWhatsapp')
                    ->searchable(['NomorWhatsapp', 'NamaKontak'])
                    ->preload()
                    ->required(),
                TextInput::make('PeranAnggota')
                    ->label('Peran Anggota')
                    ->maxLength(100),
                Toggle::make('NonAktif')
                    ->label('Nonaktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grupWhatsapp.instansi.NamaInstansi')
                    ->label('Klien')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grupWhatsapp.NamaGrup')
                    ->label('Grup')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('nomorWhatsapp.NamaKontak')
                    ->label('Nama WA')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nomorWhatsapp.NomorWhatsapp')
                    ->label('Nomor WA')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.NamaCustomer')
                    ->label('Kontak Customer')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('PeranAnggota')
                    ->label('Peran')
                    ->searchable(),
                ToggleColumn::make('NonAktif')
                    ->label('Nonaktif')
                    ->disabled(fn (): bool => ! FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_MANAGE)),
                TextColumn::make('TglBuat')
                    ->label('Dibuat')
                    ->dateTime(\App\Support\LocaleFormatter::tableDateTimeFormat())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('TglEdit')
                    ->label('Diedit')
                    ->dateTime(\App\Support\LocaleFormatter::tableDateTimeFormat())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('IdGrupWhatsapp')
                    ->label('Grup')
                    ->relationship('grupWhatsapp', 'NamaGrup')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('NonAktif')
                    ->label(__('ui.filters.status'))
                    ->placeholder(__('ui.filters.all'))
                    ->trueLabel(__('ui.filters.inactive'))
                    ->falseLabel(__('ui.filters.active')),
            ])
            ->defaultSort('PeranAnggota')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_MANAGE)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAnggotaGrupWhatsapps::route('/'),
        ];
    }
}
