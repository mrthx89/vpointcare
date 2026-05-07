<?php

namespace App\Filament\Resources\Master\HakAkseses;

use App\Filament\Resources\Master\HakAkseses\Pages\ManageHakAkseses;
use App\Models\Master\HakAkses;
use App\Models\Master\Pengguna;
use App\Support\AccessPermissions;
use App\Support\NavigationHelper;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HakAksesResource extends Resource
{
    protected static ?string $model = HakAkses::class;

    protected static ?string $slug = 'master/hak-akses';

    public static function getNavigationIcon(): string | BackedEnum | \Illuminate\Contracts\Support\Htmlable | null
    {
        return NavigationHelper::iconFor(AccessPermissions::HAK_AKSES_VIEW, 'heroicon-o-shield-check');
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationHelper::groupFor(AccessPermissions::HAK_AKSES_VIEW, __('ui.navigation.settings'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::HAK_AKSES_VIEW);
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::HAK_AKSES_VIEW, __('ui.models.hak_akses.label'));
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
        return self::canManageAccessSettings() && NavigationHelper::isActive(AccessPermissions::HAK_AKSES_VIEW);
    }

    public static function canViewAny(): bool
    {
        return self::canManageAccessSettings() && NavigationHelper::isActive(AccessPermissions::HAK_AKSES_VIEW);
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
                    ->label(__('ui.models.hak_akses.code'))
                    ->disabled()
                    ->dehydrated(false),
                Select::make('IdHakAkses')
                    ->label(__('ui.models.hak_akses.parent_group'))
                    ->options(fn (?HakAkses $record): array => HakAkses::groupOptions($record?->getKey()))
                    ->searchable()
                    ->placeholder(__('ui.models.hak_akses.no_parent'))
                    ->disabled(fn (?HakAkses $record): bool => ! $record || $record->KodeHakAkses === null || $record->KodeHakAkses === AccessPermissions::DASHBOARD_VIEW),
                TextInput::make('SortOrder')
                    ->label(__('ui.models.hak_akses.sort_order'))
                    ->numeric()
                    ->integer()
                    ->required(),
                TextInput::make('IconString')
                    ->label(__('ui.models.hak_akses.icon_string'))
                    ->placeholder('heroicon-o-shield-check')
                    ->maxLength(100)
                    ->live(debounce: 300)
                    ->helperText(fn (?string $state): string => self::characterCounter($state, 100)),
                Toggle::make('NonAktif')
                    ->label(__('ui.common.inactive')),
                TextInput::make('NamaHakAksesId')
                    ->label(__('ui.models.hak_akses.name_id'))
                    ->maxLength(150)
                    ->required()
                    ->live(debounce: 300)
                    ->helperText(fn (?string $state): string => self::characterCounter($state, 150)),
                TextInput::make('NamaHakAksesEn')
                    ->label(__('ui.models.hak_akses.name_en'))
                    ->maxLength(150)
                    ->required()
                    ->live(debounce: 300)
                    ->helperText(fn (?string $state): string => self::characterCounter($state, 150)),
                TextInput::make('ModulId')
                    ->label(__('ui.models.hak_akses.module_id'))
                    ->maxLength(100)
                    ->required()
                    ->live(debounce: 300)
                    ->helperText(fn (?string $state): string => self::characterCounter($state, 100)),
                TextInput::make('ModulEn')
                    ->label(__('ui.models.hak_akses.module_en'))
                    ->maxLength(100)
                    ->required()
                    ->live(debounce: 300)
                    ->helperText(fn (?string $state): string => self::characterCounter($state, 100)),
                Textarea::make('KeteranganId')
                    ->label(__('ui.models.hak_akses.description_id'))
                    ->rows(3)
                    ->maxLength(255)
                    ->live(debounce: 300)
                    ->helperText(fn (?string $state): string => self::characterCounter($state, 255)),
                Textarea::make('KeteranganEn')
                    ->label(__('ui.models.hak_akses.description_en'))
                    ->rows(3)
                    ->maxLength(255)
                    ->live(debounce: 300)
                    ->helperText(fn (?string $state): string => self::characterCounter($state, 255)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('parent'))
            ->columns([
                TextColumn::make('SortOrder')
                    ->label(__('ui.models.hak_akses.sort_order'))
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('TypeLabel')
                    ->label(__('ui.models.hak_akses.record_type'))
                    ->badge()
                    ->color(fn (HakAkses $record): string => match (true) {
                        $record->KodeHakAkses === AccessPermissions::DASHBOARD_VIEW => 'info',
                        $record->KodeHakAkses === null => 'warning',
                        $record->IconString !== null || $record->SortOrder !== null => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('GroupLocalized')
                    ->label(__('ui.models.hak_akses.parent_group'))
                    ->placeholder(__('ui.models.hak_akses.no_parent'))
                    ->toggleable(),
                TextColumn::make('KodeHakAkses')
                    ->label(__('ui.models.hak_akses.code'))
                    ->searchable()
                    ->sortable()
                    ->placeholder(__('ui.models.hak_akses.group_row')),
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
                TextColumn::make('ModulId')
                    ->label(__('ui.models.hak_akses.module_id'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('ModulEn')
                    ->label(__('ui.models.hak_akses.module_en'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('IconString')
                    ->label(__('ui.models.hak_akses.icon_string'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('StatusAktif')
                    ->label(__('ui.common.status'))
                    ->badge()
                    ->getStateUsing(fn (HakAkses $record): string => $record->NonAktif ? 'inactive' : 'active')
                    ->formatStateUsing(fn (string $state): string => __("ui.common.{$state}"))
                    ->color(fn (string $state): string => $state === 'inactive' ? 'danger' : 'success'),
                TextColumn::make('KeteranganId')
                    ->label(__('ui.models.hak_akses.description_id'))
                    ->limit(90)
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('KeteranganEn')
                    ->label(__('ui.models.hak_akses.description_en'))
                    ->limit(90)
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('TglEdit')
                    ->label(__('ui.common.edited_at'))
                    ->dateTime(\App\Support\LocaleFormatter::tableDateTimeFormat())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('record_type')
                    ->label(__('ui.models.hak_akses.record_type'))
                    ->options([
                        'group' => __('ui.models.hak_akses.type_group'),
                        'dashboard' => __('ui.models.hak_akses.type_dashboard'),
                        'menu' => __('ui.models.hak_akses.type_menu'),
                        'permission' => __('ui.models.hak_akses.type_permission'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::recordTypeFilter($query, $data['value'] ?? null)),
                SelectFilter::make('IdHakAkses')
                    ->label(__('ui.models.hak_akses.parent_group'))
                    ->options(fn (): array => HakAkses::groupOptions())
                    ->searchable(),
                TernaryFilter::make('NonAktif')
                    ->label(__('ui.filters.status'))
                    ->placeholder(__('ui.filters.all'))
                    ->trueLabel(__('ui.filters.inactive'))
                    ->falseLabel(__('ui.filters.active')),
            ])
            ->groups([
                Group::make('sidebar_group')
                    ->label(__('ui.models.hak_akses.parent_group'))
                    ->getTitleFromRecordUsing(fn (HakAkses $record): string => $record->GroupLocalized ?: ($record->KodeHakAkses === AccessPermissions::DASHBOARD_VIEW ? __('ui.pages.dashboard.navigation_label') : __('ui.models.hak_akses.no_parent')))
                    ->getKeyFromRecordUsing(fn (HakAkses $record): string => $record->IdHakAkses ?: ($record->KodeHakAkses === AccessPermissions::DASHBOARD_VIEW ? 'dashboard' : 'root'))
                    ->orderQueryUsing(fn (Builder $query, string $direction): Builder => self::orderBySidebarGroup($query, $direction))
                    ->collapsible(),
            ])
            ->defaultGroup('sidebar_group')
            ->defaultSort('SortOrder')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                EditAction::make()
                    ->modalHeading(fn (HakAkses $record): string => __('ui.models.hak_akses.edit_heading', ['code' => $record->KodeHakAkses ?: $record->NamaLocalized]))
                    ->visible(fn (): bool => self::canManageAccessSettings()),
            ]);
    }

    public static function canManageAccessSettings(): bool
    {
        $user = auth()->user();

        if (! $user instanceof Pengguna) {
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

    private static function recordTypeFilter(Builder $query, ?string $value): Builder
    {
        return match ($value) {
            'group' => $query->whereNull('KodeHakAkses')->whereNull('IdHakAkses'),
            'dashboard' => $query->where('KodeHakAkses', AccessPermissions::DASHBOARD_VIEW),
            'menu' => $query->whereNotNull('KodeHakAkses')->where(function (Builder $query): void {
                $query->whereNotNull('SortOrder')->orWhereNotNull('IconString');
            })->where('KodeHakAkses', '<>', AccessPermissions::DASHBOARD_VIEW),
            'permission' => $query->whereNotNull('KodeHakAkses')->whereNull('SortOrder')->whereNull('IconString')->where('KodeHakAkses', '<>', AccessPermissions::DASHBOARD_VIEW),
            default => $query,
        };
    }

    private static function orderBySidebarGroup(Builder $query, string $direction): Builder
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $query->orderByRaw(
            "
            CASE
                WHEN MHakAkses.KodeHakAkses = ? THEN 0
                WHEN MHakAkses.KodeHakAkses IS NULL AND MHakAkses.IdHakAkses IS NULL THEN 1
                WHEN MHakAkses.IdHakAkses IS NOT NULL THEN 2
                ELSE 3
            END {$direction},
            COALESCE(
                (SELECT parent_group.SortOrder FROM MHakAkses parent_group WHERE parent_group.Id = MHakAkses.IdHakAkses),
                MHakAkses.SortOrder,
                9999
            ) {$direction}
            ",
            [AccessPermissions::DASHBOARD_VIEW]
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHakAkseses::route('/'),
        ];
    }
}
