<?php

namespace App\Filament\Resources\Master\Penggunas;

use App\Filament\Resources\Master\Penggunas\Pages\ManagePenggunas;
use App\Models\Master\Pengguna;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use BackedEnum;
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
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class PenggunaResource extends Resource
{
    protected static ?string $model = Pengguna::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Pengguna Internal';

    protected static ?string $modelLabel = 'Pengguna Internal';

    protected static ?string $pluralModelLabel = 'Pengguna Internal';

    protected static ?int $navigationSort = 48;

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
                    ->label('Nama Pengguna')
                    ->maxLength(150)
                    ->required(),
                TextInput::make('Email')
                    ->email()
                    ->maxLength(150)
                    ->required(),
                Select::make('IdPeran')
                    ->label('Peran')
                    ->options(fn (): array => DB::table('MPeran')
                        ->where('NonAktif', false)
                        ->orderBy('NamaPeran')
                        ->pluck('NamaPeran', 'Id')
                        ->all())
                    ->searchable()
                    ->required(),
                TextInput::make('NomorWhatsappInternal')
                    ->label('Nomor WhatsApp Internal')
                    ->tel()
                    ->maxLength(30)
                    ->helperText('Wajib diisi agar user menerima notifikasi chat belum terbalas. Gunakan format angka, contoh 62812xxxx.'),
                Textarea::make('Alamat')
                    ->rows(3)
                    ->maxLength(500),
                FileUpload::make('FotoProfilPath')
                    ->label('Foto Profil')
                    ->disk('public')
                    ->directory('pengguna-profil')
                    ->visibility('public')
                    ->image()
                    ->avatar()
                    ->imageEditor()
                    ->maxSize(2048)
                    ->helperText('File disimpan di storage public, database hanya menyimpan path.'),
                TextInput::make('Jabatan')
                    ->maxLength(100),
                TextInput::make('Password')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Toggle::make('NonAktif')->label('Nonaktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('FotoProfilPath')
                    ->label('Foto')
                    ->circular()
                    ->getStateUsing(fn (Pengguna $record): ?string => $record->FotoProfilPath
                        ? route('public-storage.show', ['path' => ltrim((string) $record->FotoProfilPath, '/')])
                        : null),
                TextColumn::make('NamaPengguna')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('NomorWhatsappInternal')
                    ->label('Nomor WA Internal')
                    ->searchable()
                    ->placeholder('Belum diisi'),
                TextColumn::make('Jabatan')
                    ->toggleable(),
                ToggleColumn::make('NonAktif')
                    ->label('Nonaktif')
                    ->disabled(fn (): bool => ! FilamentAccess::can(AccessPermissions::USER_MANAGE)),
                TextColumn::make('TglEdit')
                    ->label('Diedit')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('NonAktif')->label('Status nonaktif'),
            ])
            ->defaultSort('NamaPengguna')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => FilamentAccess::can(AccessPermissions::USER_MANAGE)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePenggunas::route('/'),
        ];
    }
}
