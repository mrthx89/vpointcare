<?php

namespace App\Filament\Resources\Master\GrupWhatsapps;

use App\Filament\Resources\Master\GrupWhatsapps\Pages\ManageGrupWhatsapps;
use App\Models\Master\GrupWhatsapp;
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

    public static function getNavigationIcon(): string | BackedEnum | \Illuminate\Contracts\Support\Htmlable | null
    {
        return NavigationHelper::iconFor(AccessPermissions::MENU_MASTER_GRUP_WHATSAPP, Heroicon::OutlinedChatBubbleLeftRight);
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationHelper::groupFor(AccessPermissions::MENU_MASTER_GRUP_WHATSAPP, __('ui.navigation.master_data'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::MENU_MASTER_GRUP_WHATSAPP, 50);
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::MENU_MASTER_GRUP_WHATSAPP, __('ui.models.grup_whatsapp.label'));
    }

    public static function getModelLabel(): string
    {
        return __('ui.models.grup_whatsapp.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.models.grup_whatsapp.plural');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_VIEW)
            && NavigationHelper::isActive(AccessPermissions::MENU_MASTER_GRUP_WHATSAPP);
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
                    ->label(__('ui.models.grup_whatsapp.client'))
                    ->relationship('instansi', 'NamaInstansi')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('KodeGrup')
                    ->label(__('ui.models.customer.code'))
                    ->maxLength(50)
                    ->required(),
                TextInput::make('NamaGrup')
                    ->label(__('ui.models.grup_whatsapp.group_name'))
                    ->maxLength(200)
                    ->required(),
                TextInput::make('IdGrupWaha')
                    ->label(__('ui.models.grup_whatsapp.waha_group_id'))
                    ->maxLength(200),
                TextInput::make('NomorGrupWhatsapp')
                    ->label(__('ui.models.grup_whatsapp.group_number'))
                    ->maxLength(50),
                Textarea::make('Deskripsi')
                    ->label(__('ui.models.customer.notes'))
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('NonAktif')
                    ->label(__('ui.common.inactive')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instansi.NamaInstansi')
                    ->label(__('ui.models.grup_whatsapp.client'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('KodeGrup')
                    ->label(__('ui.models.customer.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('NamaGrup')
                    ->label(__('ui.models.grup_whatsapp.group_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('IdGrupWaha')
                    ->label(__('ui.models.grup_whatsapp.waha_group_id'))
                    ->searchable(),
                TextColumn::make('NomorGrupWhatsapp')
                    ->label(__('ui.models.grup_whatsapp.group_number'))
                    ->searchable(),
                TextColumn::make('Deskripsi')
                    ->label(__('ui.models.customer.notes'))
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('anggota_count')
                    ->label(__('ui.models.grup_whatsapp.member_count'))
                    ->counts('anggota')
                    ->sortable(),
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
