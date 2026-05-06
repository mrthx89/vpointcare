<?php

namespace App\Filament\Resources\Master\Pengetahuans;

use App\Filament\Resources\Master\Pengetahuans\Pages\ManagePengetahuans;
use App\Models\Master\Pengetahuan;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\NavigationHelper;
use BackedEnum;
use Filament\Actions\EditAction;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

class PengetahuanResource extends Resource
{
    protected static ?string $model = Pengetahuan::class;

    public static function getNavigationIcon(): string | BackedEnum | \Illuminate\Contracts\Support\Htmlable | null
    {
        return NavigationHelper::iconFor(AccessPermissions::KNOWLEDGE_VIEW, Heroicon::OutlinedBookOpen);
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationHelper::groupFor(AccessPermissions::KNOWLEDGE_VIEW, __('ui.navigation.assistant'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::KNOWLEDGE_VIEW, 20);
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::KNOWLEDGE_VIEW, __('ui.models.pengetahuan.label'));
    }

    public static function getModelLabel(): string
    {
        return __('ui.models.pengetahuan.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.models.pengetahuan.plural');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::KNOWLEDGE_VIEW)
            && NavigationHelper::isActive(AccessPermissions::KNOWLEDGE_VIEW);
    }

    public static function canCreate(): bool
    {
        return FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE);
    }

    public static function canEdit($record): bool
    {
        return FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE);
    }

    public static function canDelete($record): bool
    {
        return FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('KodePengetahuan')
                    ->label(__('ui.common.code'))
                    ->maxLength(50)
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Str::upper(Str::slug($state, '_')) : null),
                TextInput::make('JudulPengetahuan')
                    ->label(__('ui.models.pengetahuan.title'))
                    ->maxLength(200)
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, ?Model $record): void {
                        if ($record || ! $state) {
                            return;
                        }

                        $set('KodePengetahuan', Str::upper(Str::slug(Str::limit($state, 45, ''), '_')));
                    }),
                TextInput::make('Tag')
                    ->label('Tag')
                    ->maxLength(500)
                    ->placeholder('login,password,error,reset'),
                Textarea::make('IsiPengetahuan')
                    ->label(__('ui.models.pengetahuan.content'))
                    ->rows(10)
                    ->required()
                    ->columnSpanFull()
                    ->helperText(__('ui.models.pengetahuan.content_help')),
                Toggle::make('NonAktif')
                    ->label(__('ui.common.inactive')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('KodePengetahuan')
                    ->label(__('ui.common.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('JudulPengetahuan')
                    ->label(__('ui.models.pengetahuan.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('Tag')
                    ->label('Tag')
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('IsiPengetahuan')
                    ->label(__('ui.models.pengetahuan.content'))
                    ->limit(120)
                    ->searchable()
                    ->wrap(),
                ToggleColumn::make('NonAktif')
                    ->label(__('ui.common.inactive'))
                    ->disabled(fn (): bool => ! FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE)),
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
            ->defaultSort('JudulPengetahuan')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => FilamentAccess::can(AccessPermissions::KNOWLEDGE_MANAGE)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePengetahuans::route('/'),
        ];
    }
}
