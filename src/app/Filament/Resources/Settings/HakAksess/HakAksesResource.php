<?php

namespace App\Filament\Resources\Settings\HakAksess;

use App\Filament\Resources\Settings\HakAksess\Pages\ManageHakAksess;
use App\Models\Master\HakAkses;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HakAksesResource extends Resource
{
    protected static ?string $model = HakAkses::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('ui.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('ui.models.hak_akses.label');
    }

    public static function getModelLabel(): string
    {
        return __('ui.models.hak_akses.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.models.hak_akses.plural');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::HAK_AKSES_VIEW);
    }

    public static function canCreate(): bool
    {
        return false; // Records are seeded, not created manually
    }

    public static function canEdit($record): bool
    {
        return FilamentAccess::can(AccessPermissions::HAK_AKSES_MANAGE);
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('ui.models.hak_akses.section_id'))
                    ->description(__('ui.models.hak_akses.section_id_desc'))
                    ->icon(Heroicon::OutlinedLanguage)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('NamaHakAksesId')
                                ->label(__('ui.models.hak_akses.name_id'))
                                ->required()
                                ->maxLength(150)
                                ->live()
                                ->hint(fn ($state): string => strlen((string) $state) . ' / 150')
                                ->hintColor(fn ($state): string => strlen((string) $state) >= 140 ? 'danger' : (strlen((string) $state) >= 120 ? 'warning' : 'gray')),
                            TextInput::make('NamaHakAksesEn')
                                ->label(__('ui.models.hak_akses.name_en'))
                                ->required()
                                ->maxLength(150)
                                ->live()
                                ->hint(fn ($state): string => strlen((string) $state) . ' / 150')
                                ->hintColor(fn ($state): string => strlen((string) $state) >= 140 ? 'danger' : (strlen((string) $state) >= 120 ? 'warning' : 'gray')),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('ModulId')
                                ->label(__('ui.models.hak_akses.modul_id'))
                                ->required()
                                ->maxLength(100)
                                ->live()
                                ->hint(fn ($state): string => strlen((string) $state) . ' / 100')
                                ->hintColor(fn ($state): string => strlen((string) $state) >= 90 ? 'danger' : (strlen((string) $state) >= 75 ? 'warning' : 'gray')),
                            TextInput::make('ModulEn')
                                ->label(__('ui.models.hak_akses.modul_en'))
                                ->required()
                                ->maxLength(100)
                                ->live()
                                ->hint(fn ($state): string => strlen((string) $state) . ' / 100')
                                ->hintColor(fn ($state): string => strlen((string) $state) >= 90 ? 'danger' : (strlen((string) $state) >= 75 ? 'warning' : 'gray')),
                        ]),
                        Grid::make(2)->schema([
                            Textarea::make('KeteranganId')
                                ->label(__('ui.models.hak_akses.keterangan_id'))
                                ->maxLength(255)
                                ->rows(2)
                                ->live()
                                ->hint(fn ($state): string => strlen((string) $state) . ' / 255')
                                ->hintColor(fn ($state): string => strlen((string) $state) >= 245 ? 'danger' : (strlen((string) $state) >= 220 ? 'warning' : 'gray')),
                            Textarea::make('KeteranganEn')
                                ->label(__('ui.models.hak_akses.keterangan_en'))
                                ->maxLength(255)
                                ->rows(2)
                                ->live()
                                ->hint(fn ($state): string => strlen((string) $state) . ' / 255')
                                ->hintColor(fn ($state): string => strlen((string) $state) >= 245 ? 'danger' : (strlen((string) $state) >= 220 ? 'warning' : 'gray')),
                        ]),
                    ]),
                Section::make(__('ui.models.hak_akses.section_status'))
                    ->schema([
                        Toggle::make('NonAktif')
                            ->label(__('ui.common.inactive'))
                            ->helperText(__('ui.models.hak_akses.nonaktif_help')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('KodeHakAkses')
                    ->label(__('ui.models.hak_akses.code'))
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->copyable()
                    ->size('sm'),
                TextColumn::make('NamaLocalized')
                    ->label(__('ui.models.hak_akses.name'))
                    ->searchable(query: fn ($query, $search) => $query
                        ->where('NamaHakAksesId', 'like', "%{$search}%")
                        ->orWhere('NamaHakAksesEn', 'like', "%{$search}%")
                        ->orWhere('NamaHakAkses', 'like', "%{$search}%"))
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('NamaHakAksesId', $direction))
                    ->getStateUsing(fn (HakAkses $record): string => $record->NamaLocalized)
                    ->wrap(),
                TextColumn::make('ModulLocalized')
                    ->label(__('ui.models.hak_akses.modul'))
                    ->badge()
                    ->getStateUsing(fn (HakAkses $record): string => $record->ModulLocalized)
                    ->searchable(query: fn ($query, $search) => $query
                        ->where('ModulId', 'like', "%{$search}%")
                        ->orWhere('ModulEn', 'like', "%{$search}%")
                        ->orWhere('Modul', 'like', "%{$search}%"))
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('ModulId', $direction)),
                TextColumn::make('KeteranganLocalized')
                    ->label(__('ui.models.hak_akses.keterangan'))
                    ->getStateUsing(fn (HakAkses $record): ?string => $record->KeteranganLocalized)
                    ->wrap()
                    ->toggleable()
                    ->placeholder(__('ui.common.not_filled')),
                TextColumn::make('StatusAktif')
                    ->label(__('ui.common.status'))
                    ->badge()
                    ->getStateUsing(fn (HakAkses $record): string => $record->NonAktif ? 'inactive' : 'active')
                    ->formatStateUsing(fn (string $state): string => $state === 'active'
                        ? __('ui.filters.active')
                        : __('ui.filters.inactive'))
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
                TextColumn::make('TglEdit')
                    ->label(__('ui.models.pengguna.updated_at'))
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
            ->defaultSort('KodeHakAkses')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->recordActions([
                EditAction::make()
                    ->modalHeading(__('ui.models.hak_akses.edit_title'))
                    ->modalWidth('4xl')
                    ->visible(fn (): bool => FilamentAccess::can(AccessPermissions::HAK_AKSES_MANAGE)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHakAksess::route('/'),
        ];
    }
}
