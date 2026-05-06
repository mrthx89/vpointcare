<?php

namespace App\Filament\Resources\Master\NomorWhatsapps;

use App\Filament\Resources\Master\NomorWhatsapps\Pages\ManageNomorWhatsapps;
use App\Models\Master\NomorWhatsapp;
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

class NomorWhatsappResource extends Resource
{
    protected static ?string $model = NomorWhatsapp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?int $navigationSort = 43;

    public static function getNavigationGroup(): ?string
    {
        return __('ui.navigation.master_data');
    }

    public static function getNavigationLabel(): string
    {
        return __('ui.models.nomor_whatsapp.label');
    }

    public static function getModelLabel(): string
    {
        return __('ui.models.nomor_whatsapp.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.models.nomor_whatsapp.plural');
    }

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
                Select::make('IdCustomer')
                    ->label(__('ui.models.nomor_whatsapp.contact'))
                    ->relationship('customer', 'NamaCustomer')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('NomorWhatsapp')
                    ->label(__('ui.models.nomor_whatsapp.whatsapp_number'))
                    ->helperText(__('ui.models.nomor_whatsapp.whatsapp_number_help'))
                    ->maxLength(30)
                    ->required(),
                TextInput::make('IdWaha')
                    ->label(__('ui.models.nomor_whatsapp.waha_id'))
                    ->helperText(__('ui.models.nomor_whatsapp.waha_id_help'))
                    ->maxLength(200),
                TextInput::make('NamaKontak')
                    ->label(__('ui.models.nomor_whatsapp.contact_name'))
                    ->maxLength(150),
                TextInput::make('JabatanKontak')
                    ->label(__('ui.models.customer.job'))
                    ->maxLength(100),
                Toggle::make('NomorUtama')
                    ->label('Nomor utama'),
                Toggle::make('Terverifikasi')
                    ->label('Terverifikasi'),
                Toggle::make('NonAktif')
                    ->label(__('ui.common.inactive')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instansi.NamaInstansi')
                    ->label(__('ui.models.customer.client'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.NamaCustomer')
                    ->label(__('ui.models.nomor_whatsapp.contact'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('NomorWhatsapp')
                    ->label(__('ui.models.nomor_whatsapp.whatsapp_number'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('IdWaha')
                    ->label(__('ui.models.nomor_whatsapp.waha_id'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('NamaKontak')
                    ->label(__('ui.models.nomor_whatsapp.contact_name'))
                    ->searchable(),
                TextColumn::make('JabatanKontak')
                    ->label(__('ui.models.customer.job'))
                    ->searchable(),
                ToggleColumn::make('NomorUtama')
                    ->label('Utama')
                    ->disabled(fn (): bool => ! FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_MANAGE)),
                ToggleColumn::make('Terverifikasi')
                    ->label('Verified')
                    ->disabled(fn (): bool => ! FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_MANAGE)),
                ToggleColumn::make('NonAktif')
                    ->label(__('ui.common.inactive'))
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
                SelectFilter::make('IdCustomer')
                    ->label('Kontak')
                    ->relationship('customer', 'NamaCustomer')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('NonAktif')
                    ->label(__('ui.filters.status'))
                    ->placeholder(__('ui.filters.all'))
                    ->trueLabel(__('ui.filters.inactive'))
                    ->falseLabel(__('ui.filters.active')),
            ])
            ->defaultSort('NamaKontak')
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
            'index' => ManageNomorWhatsapps::route('/'),
        ];
    }
}
