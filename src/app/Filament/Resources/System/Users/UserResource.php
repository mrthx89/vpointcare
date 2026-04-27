<?php

namespace App\Filament\Resources\System\Users;

use App\Filament\Resources\System\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
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
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
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
                    ->visible(fn (User $record): bool => $record->status !== User::STATUS_APPROVED)
                    ->action(fn (User $record): bool => $record->update([
                        'status' => User::STATUS_APPROVED,
                        'approved_at' => now(),
                        'blocked_at' => null,
                    ])),
                Action::make('block')
                    ->label('Block')
                    ->icon(Heroicon::NoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->status !== User::STATUS_BLOCKED && $record->getKey() !== auth()->id())
                    ->action(fn (User $record): bool => $record->update([
                        'status' => User::STATUS_BLOCKED,
                        'blocked_at' => now(),
                    ])),
                Action::make('pending')
                    ->label('Pending')
                    ->icon(Heroicon::Clock)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->status !== User::STATUS_PENDING && $record->getKey() !== auth()->id())
                    ->action(fn (User $record): bool => $record->update([
                        'status' => User::STATUS_PENDING,
                        'approved_at' => null,
                        'blocked_at' => null,
                    ])),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
