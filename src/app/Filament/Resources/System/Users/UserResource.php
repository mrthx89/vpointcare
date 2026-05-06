<?php

namespace App\Filament\Resources\System\Users;

use App\Filament\Resources\System\Users\Pages\ManageUsers;
use App\Models\User;
use App\Services\Auth\UserPenggunaSyncService;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'User Login';

    protected static ?string $modelLabel = 'User Login';

    protected static ?string $pluralModelLabel = 'User Login';

    protected static ?int $navigationSort = 10;

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
                TextInput::make('name')
                    ->label('Nama')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->unique(User::class, 'email', ignoreRecord: true)
                    ->required(),
                Select::make('IdPeran')
                    ->label('Peran')
                    ->options(fn (): array => DB::table('MPeran')
                        ->where('NonAktif', false)
                        ->orderBy('NamaPeran')
                        ->pluck('NamaPeran', 'Id')
                        ->all())
                    ->default(fn (): ?string => static::defaultRoleId())
                    ->searchable()
                    ->required(),
                TextInput::make('NomorWhatsappInternal')
                    ->label('Nomor WhatsApp Internal')
                    ->tel()
                    ->maxLength(30)
                    ->helperText('Dipakai untuk notifikasi chat belum terbalas ke tim CS. Gunakan format angka, contoh 62812xxxx.'),
                Textarea::make('Alamat')
                    ->rows(3)
                    ->maxLength(500),
                TextInput::make('Jabatan')
                    ->maxLength(100),
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
                Select::make('status')
                    ->label('Status')
                    ->options(User::STATUSES)
                    ->default(User::STATUS_PENDING)
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('FotoProfilPath')
                    ->label('Foto')
                    ->circular()
                    ->getStateUsing(function (User $record): ?string {
                        $path = static::profileForUser($record)?->FotoProfilPath;

                        return $path ? route('public-storage.show', ['path' => ltrim((string) $path, '/')]) : null;
                    }),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('NamaPeran')
                    ->label('Peran')
                    ->badge()
                    ->getStateUsing(fn (User $record): ?string => static::profileForUser($record)?->NamaPeran),
                TextColumn::make('NomorWhatsappInternal')
                    ->label('WA Internal')
                    ->placeholder('Belum diisi')
                    ->getStateUsing(fn (User $record): ?string => static::profileForUser($record)?->NomorWhatsappInternal),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => User::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        User::STATUS_APPROVED => 'success',
                        User::STATUS_BLOCKED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label('Approved')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('blocked_at')
                    ->label('Blocked')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Daftar')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(User::STATUSES),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->visible(fn (User $record): bool => FilamentAccess::can(AccessPermissions::USER_MANAGE) && $record->status !== User::STATUS_APPROVED)
                    ->action(function (User $record): void {
                        abort_unless(FilamentAccess::can(AccessPermissions::USER_MANAGE), 403);

                        $record->update([
                            'status' => User::STATUS_APPROVED,
                            'approved_at' => now(),
                            'blocked_at' => null,
                        ]);

                        app(UserPenggunaSyncService::class)->syncFromUser($record->refresh());
                    }),
                Action::make('block')
                    ->label('Block')
                    ->icon(Heroicon::NoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => FilamentAccess::can(AccessPermissions::USER_MANAGE) && $record->status !== User::STATUS_BLOCKED && $record->getKey() !== auth()->id())
                    ->action(function (User $record): void {
                        abort_unless(FilamentAccess::can(AccessPermissions::USER_MANAGE), 403);

                        $record->update([
                            'status' => User::STATUS_BLOCKED,
                            'blocked_at' => now(),
                        ]);

                        app(UserPenggunaSyncService::class)->syncFromUser($record->refresh());
                    }),
                Action::make('pending')
                    ->label('Pending')
                    ->icon(Heroicon::Clock)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => FilamentAccess::can(AccessPermissions::USER_MANAGE) && $record->status !== User::STATUS_PENDING && $record->getKey() !== auth()->id())
                    ->action(function (User $record): void {
                        abort_unless(FilamentAccess::can(AccessPermissions::USER_MANAGE), 403);

                        $record->update([
                            'status' => User::STATUS_PENDING,
                            'approved_at' => null,
                            'blocked_at' => null,
                        ]);

                        app(UserPenggunaSyncService::class)->syncFromUser($record->refresh());
                    }),
                EditAction::make()
                    ->visible(fn (): bool => FilamentAccess::can(AccessPermissions::USER_MANAGE))
                    ->mutateRecordDataUsing(fn (array $data, User $record): array => array_merge($data, static::profileFormData($record)))
                    ->using(function (array $data, User $record): User {
                        abort_unless(FilamentAccess::can(AccessPermissions::USER_MANAGE), 403);

                        [$userData, $profileData] = static::splitFormData($data);

                        $record->update(static::normalizeUserData($userData, $record));
                        static::syncProfile($record->refresh(), $profileData);

                        return $record;
                    }),
            ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public static function splitFormData(array $data): array
    {
        $profileKeys = ['IdPeran', 'NomorWhatsappInternal', 'Alamat', 'Jabatan', 'FotoProfilPath'];

        return [
            Arr::except($data, $profileKeys),
            Arr::only($data, $profileKeys),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeUserData(array $data, ?User $record = null): array
    {
        $status = $data['status'] ?? User::STATUS_PENDING;

        if ($status === User::STATUS_APPROVED) {
            $data['approved_at'] = $record?->approved_at ?? now();
            $data['blocked_at'] = null;
        } elseif ($status === User::STATUS_PENDING) {
            $data['approved_at'] = null;
            $data['blocked_at'] = null;
        } elseif ($status === User::STATUS_BLOCKED) {
            $data['blocked_at'] = $record?->blocked_at ?? now();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $profileData
     */
    public static function syncProfile(User $user, array $profileData): void
    {
        app(UserPenggunaSyncService::class)->syncFromUser($user, $profileData);
    }

    /**
     * @return array<string, mixed>
     */
    public static function profileFormData(User $user): array
    {
        $profile = static::profileForUser($user);

        return [
            'IdPeran' => $profile?->IdPeran ?? static::defaultRoleId(),
            'NomorWhatsappInternal' => $profile?->NomorWhatsappInternal,
            'Alamat' => $profile?->Alamat,
            'Jabatan' => $profile?->Jabatan,
            'FotoProfilPath' => $profile?->FotoProfilPath,
        ];
    }

    public static function profileForUser(User $user): ?object
    {
        return DB::table('MPengguna as p')
            ->leftJoin('MPeran as r', 'r.Id', '=', 'p.IdPeran')
            ->select([
                'p.IdPeran',
                'p.NomorWhatsappInternal',
                DatabaseSchema::hasColumn('MPengguna', 'Alamat') ? 'p.Alamat' : DB::raw('NULL as Alamat'),
                'p.Jabatan',
                'p.FotoProfilPath',
                'r.NamaPeran',
            ])
            ->where(function ($query) use ($user): void {
                $query
                    ->where('p.UserId', $user->getKey())
                    ->orWhere('p.Email', $user->email);
            })
            ->first();
    }

    public static function defaultRoleId(): ?string
    {
        return DB::table('MPeran')->where('KodePeran', 'CS')->value('Id')
            ?? DB::table('MPeran')->where('KodePeran', 'ADMIN')->value('Id')
            ?? DB::table('MPeran')->orderBy('NamaPeran')->value('Id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
