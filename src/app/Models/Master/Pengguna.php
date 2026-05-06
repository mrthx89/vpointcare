<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

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
            'TglBuat' => 'datetime',
            'TglEdit' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $pengguna): void {
            $pengguna->TglEdit = now();
        });
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->NamaPengguna);
    }

    protected function email(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->Email);
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
        return (string) $this->Email;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return ! (bool) $this->NonAktif;
    }

    public function roleCode(): ?string
    {
        $roleCode = DB::table('MPeran')
            ->where('Id', $this->IdPeran)
            ->where('NonAktif', false)
            ->value('KodePeran');

        return $roleCode ? (string) $roleCode : null;
    }

    /**
     * @return array<int, string>
     */
    public function permissionCodes(): array
    {
        if (! $this->IdPeran) {
            return [];
        }

        return DB::table('MPeranHakAkses as pr')
            ->join('MPeran as r', 'r.Id', '=', 'pr.IdPeran')
            ->join('MHakAkses as h', 'h.Id', '=', 'pr.IdHakAkses')
            ->where('pr.IdPeran', $this->IdPeran)
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
        return $this->FotoProfilPath
            ? route('public-storage.show', ['path' => ltrim((string) $this->FotoProfilPath, '/')])
            : null;
    }
}
