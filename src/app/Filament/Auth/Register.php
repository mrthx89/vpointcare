<?php

namespace App\Filament\Auth;

use App\Http\Responses\Auth\PendingRegistrationResponse;
use App\Models\Master\Pengguna;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Events\Registered;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Register extends BaseRegister
{
    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        if ($this->isRegisterRateLimited($this->data['email'] ?? '')) {
            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function (): Model {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        Notification::make()
            ->title(__('ui.auth.register_success_title'))
            ->body(__('ui.auth.register_success_body'))
            ->success()
            ->send();

        return app(PendingRegistrationResponse::class);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        $data = parent::mutateFormDataBeforeRegister($data);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        return Pengguna::query()->create([
            'NamaPengguna' => (string) ($data['name'] ?? $data['email']),
            'Email' => (string) $data['email'],
            'Password' => Hash::make((string) $data['password']),
            'IdPeran' => $this->defaultRoleId(),
            'NonAktif' => true,
        ]);
    }

    public function getTitle(): string | Htmlable
    {
        return __('ui.auth.register_title');
    }

    public function getHeading(): string | Htmlable | null
    {
        return __('ui.auth.register_heading');
    }

    public function getRegisterFormAction(): Action
    {
        return parent::getRegisterFormAction()
            ->label(__('ui.auth.register_action'));
    }

    private function defaultRoleId(): ?string
    {
        return DB::table('MPeran')->where('KodePeran', 'CS')->value('Id')
            ?? DB::table('MPeran')->where('KodePeran', 'ADMIN')->value('Id')
            ?? DB::table('MPeran')->orderBy('NamaPeran')->value('Id');
    }
}
