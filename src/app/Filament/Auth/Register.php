<?php

namespace App\Filament\Auth;

use App\Http\Responses\Auth\PendingRegistrationResponse;
use App\Models\User;
use App\Services\Auth\UserPenggunaSyncService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Events\Registered;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

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

        app(UserPenggunaSyncService::class)->syncFromUser($user);

        Notification::make()
            ->title('Registrasi berhasil')
            ->body('Akun Anda menunggu approval admin sebelum bisa login.')
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
        $data['status'] = User::STATUS_PENDING;
        $data['approved_at'] = null;
        $data['blocked_at'] = null;

        return $data;
    }

    public function getTitle(): string | Htmlable
    {
        return 'Daftar Akun';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Daftar Akun VPoint Care';
    }

    public function getRegisterFormAction(): Action
    {
        return parent::getRegisterFormAction()
            ->label('Daftar');
    }
}
