<?php

namespace App\Filament\Resources\Master\Instansis;

use App\Filament\Resources\Master\Instansis\Pages\ManageInstansis;
use App\Models\Master\Instansi;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class InstansiResource extends Resource
{
    protected static ?string $model = Instansi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 41;

    public static function getNavigationGroup(): ?string
    {
        return __('ui.navigation.master_data');
    }

    public static function getNavigationLabel(): string
    {
        return __('ui.models.instansi.label');
    }

    public static function getModelLabel(): string
    {
        return __('ui.models.instansi.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.models.instansi.plural');
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
                TextInput::make('KodeInstansi')
                    ->label(__('ui.models.instansi.code'))
                    ->maxLength(50)
                    ->required(),
                TextInput::make('NamaInstansi')
                    ->label(__('ui.models.instansi.name'))
                    ->maxLength(200)
                    ->required(),
                Textarea::make('Alamat')
                    ->label(__('ui.models.instansi.address'))
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('Kota')->maxLength(100),
                TextInput::make('Provinsi')->maxLength(100),
                TextInput::make('Telepon')->label(__('ui.models.instansi.phone'))->tel()->maxLength(50),
                TextInput::make('Email')->email()->maxLength(150),
                TextInput::make('Website')->url()->maxLength(200),
                Toggle::make('NonAktif')->label(__('ui.common.inactive')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('KodeInstansi')
                    ->label(__('ui.models.instansi.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('NamaInstansi')
                    ->label(__('ui.models.instansi.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('Kota')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('Provinsi')
                    ->toggleable(),
                TextColumn::make('Telepon')
                    ->label(__('ui.models.instansi.phone'))
                    ->searchable(),
                TextColumn::make('Email')
                    ->searchable(),
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
                TernaryFilter::make('NonAktif')
                    ->label(__('ui.filters.status'))
                    ->placeholder(__('ui.filters.all'))
                    ->trueLabel(__('ui.filters.inactive'))
                    ->falseLabel(__('ui.filters.active')),
            ])
            ->defaultSort('NamaInstansi')
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
            'index' => ManageInstansis::route('/'),
        ];
    }
}
