<?php

namespace App\Filament\Resources\Master\GrupWhatsapps;

use App\Filament\Resources\Master\GrupWhatsapps\Pages\ManageGrupWhatsapps;
use App\Models\Master\GrupWhatsapp;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class GrupWhatsappResource extends Resource
{
    protected static ?string $model = GrupWhatsapp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Grup WhatsApp';

    protected static ?string $modelLabel = 'Grup WhatsApp';

    protected static ?string $pluralModelLabel = 'Grup WhatsApp';

    protected static ?int $navigationSort = 44;

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
                Select::make('IdInstansi')
                    ->label('Klien / Instansi')
                    ->relationship('instansi', 'NamaInstansi')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('KodeGrup')
                    ->label('Kode Grup')
                    ->maxLength(50)
                    ->required(),
                TextInput::make('NamaGrup')
                    ->label('Nama Grup')
                    ->maxLength(200)
                    ->required(),
                TextInput::make('IdGrupWaha')
                    ->label('ID Grup WAHA')
                    ->maxLength(200),
                TextInput::make('NomorGrupWhatsapp')
                    ->label('Nomor Grup WhatsApp')
                    ->maxLength(50),
                Textarea::make('Deskripsi')
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('NonAktif')
                    ->label('Nonaktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instansi.NamaInstansi')
                    ->label('Klien')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('KodeGrup')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('NamaGrup')
                    ->label('Nama Grup')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('IdGrupWaha')
                    ->label('ID Grup WAHA')
                    ->searchable(),
                TextColumn::make('NomorGrupWhatsapp')
                    ->label('Nomor Grup')
                    ->searchable(),
                TextColumn::make('Deskripsi')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('anggota_count')
                    ->label('Anggota')
                    ->counts('anggota')
                    ->sortable(),
                ToggleColumn::make('NonAktif')
                    ->label('Nonaktif')
                    ->disabled(fn (): bool => ! FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_MANAGE)),
                TextColumn::make('TglBuat')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('TglEdit')
                    ->label('Diedit')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('IdInstansi')
                    ->label('Klien')
                    ->relationship('instansi', 'NamaInstansi')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('NonAktif')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Nonaktif')
                    ->falseLabel('Aktif'),
            ])
            ->defaultSort('NamaGrup')
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
            'index' => ManageGrupWhatsapps::route('/'),
        ];
    }
}
