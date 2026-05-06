<?php

namespace App\Filament\Actions;

use App\Models\Master\Pengguna;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EditOwnProfileAction
{
    public static function make(): Action
    {
        return Action::make('profile')
            ->label(fn (): string => self::currentUserName())
            ->icon('heroicon-o-user-circle')
            ->sort(-1)
            ->modalHeading(__('ui.profile.heading'))
            ->modalDescription(__('ui.profile.description'))
            ->modalWidth('2xl')
            ->modalSubmitActionLabel(__('ui.profile.submit'))
            ->fillForm(fn (): array => self::formData())
            ->form([
                Section::make(__('ui.profile.data_section'))
                    ->schema([
                        FileUpload::make('FotoProfilPath')
                            ->label(__('ui.profile.photo'))
                            ->disk('public')
                            ->directory('pengguna-profil')
                            ->visibility('public')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->maxSize(2048),
                        TextInput::make('name')
                            ->label(__('ui.profile.name'))
                            ->required()
                            ->maxLength(255),
                        Textarea::make('Alamat')
                            ->label(__('ui.profile.address'))
                            ->rows(3)
                            ->maxLength(500),
                        TextInput::make('NomorWhatsappInternal')
                            ->label(__('ui.profile.whatsapp'))
                            ->tel()
                            ->maxLength(30)
                            ->helperText(__('ui.profile.whatsapp_hint')),
                    ])
                    ->columns(1),
                Section::make(__('ui.profile.password_section'))
                    ->schema([
                        TextInput::make('password')
                            ->label(__('ui.profile.new_password'))
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->same('passwordConfirmation')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->autocomplete('new-password'),
                        TextInput::make('passwordConfirmation')
                            ->label(__('ui.profile.confirm_password'))
                            ->password()
                            ->revealable()
                            ->required(fn (Get $get): bool => filled($get('password')))
                            ->visible(fn (Get $get): bool => filled($get('password')))
                            ->dehydrated(false)
                            ->autocomplete('new-password'),
                        TextInput::make('currentPassword')
                            ->label(__('ui.profile.current_password'))
                            ->password()
                            ->revealable()
                            ->currentPassword(guard: 'web')
                            ->required(fn (Get $get): bool => filled($get('password')))
                            ->visible(fn (Get $get): bool => filled($get('password')))
                            ->dehydrated(false)
                            ->autocomplete('current-password'),
                    ])
                    ->columns(1),
            ])
            ->action(fn (array $data) => self::save($data));
    }

    private static function currentUserName(): string
    {
        $user = Filament::auth()->user();

        if (! $user instanceof Pengguna) {
            return __('ui.profile.fallback_label');
        }

        return filled($user->NamaPengguna) ? (string) $user->NamaPengguna : __('ui.profile.fallback_label');
    }

    /**
     * @return array<string, mixed>
     */
    private static function formData(): array
    {
        $user = Filament::auth()->user();

        if (! $user instanceof Pengguna) {
            return [];
        }

        return [
            'name' => $user->NamaPengguna,
            'Alamat' => $user->Alamat,
            'NomorWhatsappInternal' => $user->NomorWhatsappInternal,
            'FotoProfilPath' => $user->FotoProfilPath,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function save(array $data): void
    {
        $user = Filament::auth()->user();

        abort_unless($user instanceof Pengguna, 403);

        DB::transaction(function () use ($user, $data): void {
            $userData = [
                'NamaPengguna' => (string) $data['name'],
                'Alamat' => $data['Alamat'] ?? null,
                'NomorWhatsappInternal' => $data['NomorWhatsappInternal'] ?? null,
                'FotoProfilPath' => $data['FotoProfilPath'] ?? null,
            ];

            if (filled($data['password'] ?? null)) {
                $userData['Password'] = Hash::make((string) $data['password']);
            }

            $user->forceFill($userData)->save();
        });

        Notification::make()
            ->title(__('ui.profile.saved'))
            ->success()
            ->send();
    }
}
