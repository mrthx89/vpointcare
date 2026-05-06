<?php

namespace App\Filament\Resources\Master\HakAkseses;

use App\Filament\Resources\Master\HakAkseses\Pages\ManageHakAkseses;
use App\Models\Master\HakAkses;
use App\Models\Master\Pengguna;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class HakAksesResource extends Resource
{
    protected static ?string $model = HakAkses::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'master/hak-akses';

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

    public static function shouldRegisterNavigation(): bool
    {
        return self::canManageAccessSettings();
    }

    public static function canViewAny(): bool
    {
        return self::canManageAccessSettings();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return self::canManageAccessSettings();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('KodeHakAkses')
                    ->label(__('ui.common.code'))
                    ->disabled()
                    ->dehydrated(false),
                Toggle::make('NonAktif')
                    ->label(__('ui.common.inactive')),
                TextInput::make('NamaHakAksesId')
                    ->label(__('ui.models.hak_akses.name_id'))
                    ->maxLength(150)
                    ->required()
                    ->live(debounce: 300)
                    ->helperText(fn(?string $state): string => self::characterCounter($state, 150)),
                TextInput::make('NamaHakAksesEn')
                    ->label(__('ui.models.hak_akses.name_en'))
                    ->maxLength(150)
                    ->required()
                    ->live(debounce: 300)
                    ->helperText(fn(?string $state): string => self::characterCounter($state, 150)),
                TextInput::make('ModulId')
                    ->label(__('ui.models.hak_akses.module_id'))
                    ->maxLength(100)
                    ->required()
                    ->live(debounce: 300)
                    ->helperText(fn(?string $state): string => self::characterCounter($state, 100)),
                TextInput::make('ModulEn')
                    ->label(__('ui.models.hak_akses.module_en'))
                    ->maxLength(100)
                    ->required()
                    ->live(debounce: 300)
                    ->helperText(fn(?string $state): string => self::characterCounter($state, 100)),
                Textarea::make('KeteranganId')
                    ->label(__('ui.models.hak_akses.description_id'))
                    ->rows(3)
                    ->maxLength(255)
                    ->live(debounce: 300)
                    ->helperText(fn(?string $state): string => self::characterCounter($state, 255)),
                Textarea::make('KeteranganEn')
                    ->label(__('ui.models.hak_akses.description_en'))
                    ->rows(3)
                    ->maxLength(255)
                    ->live(debounce: 300)
                    ->helperText(fn(?string $state): string => self::characterCounter($state, 255))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('KodeHakAkses')
                    ->label(__('ui.common.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ModulId')
                    ->label(__('ui.models.hak_akses.module_id'))
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('ModulEn')
                    ->label(__('ui.models.hak_akses.module_en'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('NamaHakAksesId')
                    ->label(__('ui.models.hak_akses.name_id'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('NamaHakAksesEn')
                    ->label(__('ui.models.hak_akses.name_en'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('KeteranganId')
                    ->label(__('ui.models.hak_akses.description_id'))
                    ->limit(90)
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('KeteranganEn')
                    ->label(__('ui.models.hak_akses.description_en'))
                    ->limit(90)
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('StatusAktif')
                    ->label(__('ui.common.status'))
                    ->badge()
                    ->getStateUsing(fn(HakAkses $record): string => $record->NonAktif ? 'inactive' : 'active')
                    ->formatStateUsing(fn(string $state): string => __("ui.common.{$state}"))
                    ->color(fn(string $state): string => $state === 'inactive' ? 'danger' : 'success'),
                TextColumn::make('TglEdit')
                    ->label(__('ui.common.edited_at'))
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
            ->defaultSort('ModulId')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                EditAction::make()
                    ->modalHeading(fn(HakAkses $record): string => __('ui.models.hak_akses.edit_heading', ['code' => $record->KodeHakAkses]))
                    ->visible(fn(): bool => self::canManageAccessSettings()),
            ]);
    }

    public static function canManageAccessSettings(): bool
    {
        $user = auth()->user();

        if (!$user instanceof Pengguna) {
            return false;
        }

        return in_array($user->roleCode(), ['ADMIN', 'SUPERVISOR_CS'], true);
    }

    private static function characterCounter(?string $state, int $max): string
    {
        return __('ui.models.hak_akses.character_counter', [
            'count' => mb_strlen((string) $state),
            'max' => $max,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHakAkseses::route('/'),
        ];
    }
}
