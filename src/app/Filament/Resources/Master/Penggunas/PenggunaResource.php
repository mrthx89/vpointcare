<?php

namespace App\Filament\Resources\Master\Penggunas;

use App\Filament\Resources\Master\Penggunas\Pages\ManagePenggunas;
use App\Models\Master\Pengguna;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class PenggunaResource extends Resource
{
    protected static ?string $model = Pengguna::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('ui.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('ui.models.pengguna.label');
    }

    public static function getModelLabel(): string
    {
        return __('ui.models.pengguna.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.models.pengguna.plural');
    }

    public static function canViewAny(): bool
    {
        return FilamentAccess::can(AccessPermissions::USER_VIEW);
    }

    public static function canCreate(): bool
    {
        return FilamentAccess::can(AccessPermissions::USER_MANAGE);
    }

    public static function canEdit($record): bool
    {
        return FilamentAccess::can(AccessPermissions::USER_MANAGE);
    }

    public static function canDelete($record): bool
    {
        return FilamentAccess::can(AccessPermissions::USER_MANAGE);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('NamaPengguna')
                    ->label(__('ui.models.pengguna.name'))
                    ->maxLength(150)
                    ->required(),
                TextInput::make('Email')
                    ->email()
                    ->maxLength(150)
                    ->unique(table: 'MPengguna', column: 'Email', ignoreRecord: true)
                    ->required(),
                Select::make('IdPeran')
                    ->label(__('ui.models.pengguna.role'))
                    ->options(fn (): array => DB::table('MPeran')
                        ->where('NonAktif', false)
                        ->orderBy('NamaPeran')
                        ->pluck('NamaPeran', 'Id')
                        ->all())
                    ->default(fn (): ?string => static::defaultRoleId())
                    ->searchable()
                    ->required(),
                TextInput::make('NomorWhatsappInternal')
                    ->label(__('ui.models.pengguna.internal_wa'))
                    ->tel()
                    ->maxLength(30)
                    ->helperText(__('ui.models.pengguna.internal_wa_help')),
                Textarea::make('Alamat')
                    ->label(__('ui.models.pengguna.address'))
                    ->rows(3)
                    ->maxLength(500),
                FileUpload::make('FotoProfilPath')
                    ->label(__('ui.models.pengguna.photo'))
                    ->disk('public')
                    ->directory('pengguna-profil')
                    ->visibility('public')
                    ->image()
                    ->avatar()
                    ->imageEditor()
                    ->maxSize(2048)
                    ->helperText(__('ui.models.pengguna.photo_help')),
                TextInput::make('Jabatan')
                    ->label(__('ui.models.pengguna.job'))
                    ->maxLength(100),
                TextInput::make('Password')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Toggle::make('NonAktif')->label(__('ui.common.inactive')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('FotoProfilPath')
                    ->label(__('ui.models.pengguna.photo_table'))
                    ->circular()
                    ->getStateUsing(fn (Pengguna $record): ?string => $record->FotoProfilPath
                        ? route('public-storage.show', ['path' => ltrim((string) $record->FotoProfilPath, '/')])
                        : null),
                TextColumn::make('NamaPengguna')
                    ->label(__('ui.models.pengguna.name_table'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('NamaPeran')
                    ->label(__('ui.models.pengguna.role_table'))
                    ->badge()
                    ->getStateUsing(fn (Pengguna $record): ?string => static::roleName($record->IdPeran)),
                TextColumn::make('NomorWhatsappInternal')
                    ->label(__('ui.models.pengguna.internal_wa_table'))
                    ->searchable()
                    ->placeholder(__('ui.common.not_filled')),
                TextColumn::make('Jabatan')
                    ->label(__('ui.models.pengguna.job'))
                    ->toggleable(),
                TextColumn::make('StatusAktif')
                    ->label(__('ui.models.pengguna.status'))
                    ->badge()
                    ->getStateUsing(fn (Pengguna $record): string => $record->NonAktif ? Pengguna::STATUS_INACTIVE : Pengguna::STATUS_ACTIVE)
                    ->formatStateUsing(fn (string $state): string => Pengguna::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => $state === Pengguna::STATUS_ACTIVE ? 'success' : 'danger'),
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
            ->defaultSort('NamaPengguna')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                Action::make('activate')
                    ->label(__('ui.models.pengguna.activate'))
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->visible(fn (Pengguna $record): bool => FilamentAccess::can(AccessPermissions::USER_MANAGE) && $record->NonAktif)
                    ->action(function (Pengguna $record): void {
                        abort_unless(FilamentAccess::can(AccessPermissions::USER_MANAGE), 403);

                        $record->update(['NonAktif' => false]);
                    }),
                Action::make('deactivate')
                    ->label(__('ui.models.pengguna.deactivate'))
                    ->icon(Heroicon::NoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(__('ui.models.pengguna.deactivate_confirm'))
                    ->visible(fn (Pengguna $record): bool => FilamentAccess::can(AccessPermissions::USER_MANAGE) && ! $record->NonAktif && $record->getKey() !== auth()->id())
                    ->action(function (Pengguna $record): void {
                        abort_unless(FilamentAccess::can(AccessPermissions::USER_MANAGE), 403);

                        $record->update(['NonAktif' => true]);
                    }),
                EditAction::make()
                    ->visible(fn (): bool => FilamentAccess::can(AccessPermissions::USER_MANAGE)),
            ]);
    }

    public static function defaultRoleId(): ?string
    {
        return DB::table('MPeran')->where('KodePeran', 'CS')->value('Id')
            ?? DB::table('MPeran')->where('KodePeran', 'ADMIN')->value('Id')
            ?? DB::table('MPeran')->orderBy('NamaPeran')->value('Id');
    }

    public static function roleName(?string $roleId): ?string
    {
        if (! $roleId) {
            return null;
        }

        return DB::table('MPeran')->where('Id', $roleId)->value('NamaPeran');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePenggunas::route('/'),
        ];
    }
}
