<?php

namespace App\Filament\Resources\Master\HariLiburs;

use App\Filament\Resources\Master\HariLiburs\Pages\ManageHariLiburs;
use App\Models\Master\HariLibur;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Hari Libur';

    protected static ?string $modelLabel = 'Hari Libur';

    protected static ?string $pluralModelLabel = 'Hari Libur';

    protected static ?int $navigationSort = 45;

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::HOLIDAY_VIEW);
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
                    ->label('Tanggal libur')
                    ->native(false)
                    ->required(),
                TextInput::make('NamaHariLibur')
                    ->label('Nama hari libur')
                    ->maxLength(200)
                    ->required(),
                Toggle::make('BerlakuTahunan')
                    ->label('Berulang setiap tahun')
                    ->helperText('Aktifkan untuk libur tetap seperti tanggal ulang tahun perusahaan.'),
                Textarea::make('Keterangan')
                    ->rows(3)
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Toggle::make('NonAktif')
                    ->label('Nonaktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('TanggalLibur')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('NamaHariLibur')
                    ->label('Nama hari libur')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('Keterangan')
                    ->limit(100)
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                ToggleColumn::make('BerlakuTahunan')
                    ->label('Tahunan')
                    ->disabled(fn (): bool => ! FilamentAccess::can(AccessPermissions::HOLIDAY_MANAGE)),
                ToggleColumn::make('NonAktif')
                    ->label('Nonaktif')
                    ->disabled(fn (): bool => ! FilamentAccess::can(AccessPermissions::HOLIDAY_MANAGE)),
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
