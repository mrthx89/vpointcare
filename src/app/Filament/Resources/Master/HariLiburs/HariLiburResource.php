<?php

namespace App\Filament\Resources\Master\HariLiburs;

use App\Filament\Resources\Master\HariLiburs\Pages\ManageHariLiburs;
use App\Models\Master\HariLibur;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\NavigationHelper;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class HariLiburResource extends Resource
{
    protected static ?string $model = HariLibur::class;

    public static function getNavigationIcon(): string | BackedEnum | \Illuminate\Contracts\Support\Htmlable | null
    {
        return NavigationHelper::iconFor(AccessPermissions::HOLIDAY_VIEW, Heroicon::OutlinedCalendarDays);
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationHelper::groupFor(AccessPermissions::HOLIDAY_VIEW, __('ui.navigation.master_data'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::HOLIDAY_VIEW, 70);
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::HOLIDAY_VIEW, __('ui.models.holiday.label'));
    }

    public static function getModelLabel(): string
    {
        return __('ui.models.holiday.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.models.holiday.plural');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::HOLIDAY_VIEW)
            && NavigationHelper::isActive(AccessPermissions::HOLIDAY_VIEW);
    }

    public static function canCreate(): bool
    {
        return FilamentAccess::can(AccessPermissions::HOLIDAY_MANAGE);
    }

    public static function canEdit($record): bool
    {
        return FilamentAccess::can(AccessPermissions::HOLIDAY_MANAGE);
    }

    public static function canDelete($record): bool
    {
        return FilamentAccess::can(AccessPermissions::HOLIDAY_MANAGE);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('TanggalLibur')
                    ->label(__('ui.models.holiday.date'))
                    ->native(false)
                    ->required(),
                TextInput::make('NamaHariLibur')
                    ->label(__('ui.models.holiday.name'))
                    ->maxLength(200)
                    ->required(),
                Toggle::make('BerlakuTahunan')
                    ->label(__('ui.models.holiday.repeat_yearly'))
                    ->helperText(__('ui.models.holiday.repeat_yearly_help')),
                Textarea::make('Keterangan')
                    ->label(__('ui.models.holiday.notes'))
                    ->rows(3)
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Toggle::make('NonAktif')
                    ->label(__('ui.common.inactive')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('TanggalLibur')
                    ->label(__('ui.models.holiday.date'))
                    ->date(\App\Support\LocaleFormatter::tableDateFormat())
                    ->sortable(),
                TextColumn::make('NamaHariLibur')
                    ->label(__('ui.models.holiday.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('Keterangan')
                    ->label(__('ui.models.holiday.notes'))
                    ->limit(100)
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                ToggleColumn::make('BerlakuTahunan')
                    ->label(__('ui.models.holiday.repeat_yearly'))
                    ->disabled(fn (): bool => ! FilamentAccess::can(AccessPermissions::HOLIDAY_MANAGE)),
                ToggleColumn::make('NonAktif')
                    ->label(__('ui.common.inactive'))
                    ->disabled(fn (): bool => ! FilamentAccess::can(AccessPermissions::HOLIDAY_MANAGE)),
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
                TernaryFilter::make('BerlakuTahunan')
                    ->label(__('ui.filters.holiday_type'))
                    ->placeholder(__('ui.filters.all'))
                    ->trueLabel(__('ui.filters.annual'))
                    ->falseLabel(__('ui.filters.once')),
                TernaryFilter::make('NonAktif')
                    ->label(__('ui.filters.status'))
                    ->placeholder(__('ui.filters.all'))
                    ->trueLabel(__('ui.filters.inactive'))
                    ->falseLabel(__('ui.filters.active')),
            ])
            ->defaultSort('TanggalLibur', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => FilamentAccess::can(AccessPermissions::HOLIDAY_MANAGE)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHariLiburs::route('/'),
        ];
    }
}
