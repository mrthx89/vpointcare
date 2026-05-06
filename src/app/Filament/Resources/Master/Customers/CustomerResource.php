<?php

namespace App\Filament\Resources\Master\Customers;

use App\Filament\Resources\Master\Customers\Pages\ManageCustomers;
use App\Models\Master\Customer;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Kontak Customer';

    protected static ?string $modelLabel = 'Kontak Customer';

    protected static ?string $pluralModelLabel = 'Kontak Customer';

    protected static ?int $navigationSort = 42;

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
                TextInput::make('KodeCustomer')
                    ->label('Kode Kontak')
                    ->maxLength(50)
                    ->required(),
                TextInput::make('NamaCustomer')
                    ->label('Nama Kontak')
                    ->maxLength(200)
                    ->required(),
                TextInput::make('Jabatan')->maxLength(100),
                TextInput::make('Email')->email()->maxLength(150),
                TextInput::make('Telepon')->tel()->maxLength(50),
                Textarea::make('Catatan')
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('NonAktif')->label('Nonaktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('KodeCustomer')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('instansi.NamaInstansi')
                    ->label('Klien')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('NamaCustomer')
                    ->label('Kontak')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('Jabatan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('Email')
                    ->searchable(),
                TextColumn::make('Telepon')
                    ->searchable(),
                TextColumn::make('nomor_whatsapp_count')
                    ->label('Nomor')
                    ->counts('nomorWhatsapp')
                    ->sortable(),
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
                SelectFilter::make('IdInstansi')
                    ->label('Klien')
                    ->relationship('instansi', 'NamaInstansi')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('NonAktif')
                    ->label(__('ui.filters.status'))
                    ->placeholder(__('ui.filters.all'))
                    ->trueLabel(__('ui.filters.inactive'))
                    ->falseLabel(__('ui.filters.active')),
            ])
            ->defaultSort('NamaCustomer')
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
            'index' => ManageCustomers::route('/'),
        ];
    }
}
