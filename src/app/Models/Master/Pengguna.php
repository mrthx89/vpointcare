<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Pengguna extends Authenticatable implements FilamentUser, HasAvatar
{
    use Notifiable;
    use UsesSqlServerUuid;

    protected $table = 'MPengguna';

    protected $guarded = ['Id'];

    protected $hidden = [
        'Password',
        'RememberToken',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const REGISTRATION_APPROVED = 'approved';

    public const REGISTRATION_PENDING = 'pending';

    public const REGISTRATION_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_INACTIVE => 'Nonaktif',
    ];

    protected function casts(): array
    {
        return [
            'Password' => 'hashed',
            'NonAktif' => 'boolean',
            'EmailTerverifikasiPada' => 'datetime',
            'LoginTerakhirPada' => 'datetime',
            'RegistrasiExternalPada' => 'datetime',
            'TglBuat' => 'datetime',
            'TglEdit' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $pengguna): void {
            $currentUserId = Auth::id();

            if ($currentUserId !== null && $pengguna->isDirty('NonAktif') && (bool) $pengguna->NonAktif && (string) $pengguna->getKey() === (string) $currentUserId) {
                throw ValidationException::withMessages([
                    'NonAktif' => __('ui.models.pengguna.self_deactivate_denied'),
                ]);
            }

            $pengguna->TglEdit = now();
        });
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn ($value, array $attributes): ?string => $attributes['NamaPengguna'] ?? null);
    }

    protected function email(): Attribute
    {
        return Attribute::get(fn ($value, array $attributes): ?string => $attributes['Email'] ?? null);
    }

    public function getAuthPasswordName(): string
    {
        return 'Password';
    }

    public function getRememberTokenName(): string
    {
        return 'RememberToken';
    }

    public function getEmailForPasswordReset(): string
    {
        return (string) $this->getRawOriginal('Email');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return ! (bool) $this->getRawOriginal('NonAktif')
            && filled($this->getRawOriginal('IdPeran'))
            && $this->roleCode() !== null
            && (($this->getRawOriginal('StatusRegistrasi') ?: self::REGISTRATION_APPROVED) === self::REGISTRATION_APPROVED);
    }

    public function externalIdentities(): HasMany
    {
        return $this->hasMany(\App\Models\Auth\PenggunaExternalIdentity::class, 'IdPengguna', 'Id');
    }

    public function roleCode(): ?string
    {
        $roleId = $this->getRawOriginal('IdPeran');

        if (! $roleId) {
            return null;
        }

        $roleCode = DB::table('MPeran')
            ->where('Id', $roleId)
            ->where('NonAktif', false)
            ->value('KodePeran');

        return $roleCode ? (string) $roleCode : null;
    }

    /**
     * @return array<int, string>
     */
    public function permissionCodes(): array
    {
        $roleId = $this->getRawOriginal('IdPeran');

        if (! $roleId) {
            return [];
        }

        return DB::table('MPeranHakAkses as pr')
            ->join('MPeran as r', 'r.Id', '=', 'pr.IdPeran')
            ->join('MHakAkses as h', 'h.Id', '=', 'pr.IdHakAkses')
            ->where('pr.IdPeran', $roleId)
            ->where('r.NonAktif', false)
            ->where('pr.NonAktif', false)
            ->where('h.NonAktif', false)
            ->distinct()
            ->pluck('h.KodeHakAkses')
            ->map(fn ($code): string => (string) $code)
            ->values()
            ->all();
    }

    public function hasPermissionCode(string $permission): bool
    {
        return in_array($permission, $this->permissionCodes(), true);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function hasAnyPermissionCode(array $permissions): bool
    {
        return count(array_intersect($permissions, $this->permissionCodes())) > 0;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $path = $this->getRawOriginal('FotoProfilPath');

        return $path
            ? route('public-storage.show', ['path' => ltrim((string) $path, '/')])
            : null;
    }
}
