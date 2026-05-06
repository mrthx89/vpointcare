<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'email', 'password', 'status', 'approved_at', 'blocked_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUSES = [
        self::STATUS_PENDING => 'Menunggu Approval',
        self::STATUS_APPROVED => 'Aktif',
        self::STATUS_BLOCKED => 'Blocked',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'blocked_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function roleCode(): ?string
    {
        if (
            ! Schema::hasTable('MPengguna')
            || ! Schema::hasTable('MPeran')
            || ! Schema::hasColumn('MPengguna', 'UserId')
        ) {
            return null;
        }

        $roleCode = DB::table('MPengguna as p')
            ->join('MPeran as r', 'r.Id', '=', 'p.IdPeran')
            ->where(function ($query): void {
                $query
                    ->where('p.UserId', $this->getKey())
                    ->orWhere('p.Email', $this->email);
            })
            ->where('p.NonAktif', false)
            ->where('r.NonAktif', false)
            ->value('r.KodePeran');

        return $roleCode ? (string) $roleCode : null;
    }

    /**
     * @return array<int, string>
     */
    public function permissionCodes(): array
    {
        if (
            ! Schema::hasTable('MPengguna')
            || ! Schema::hasTable('MPeran')
            || ! Schema::hasTable('MPeranHakAkses')
            || ! Schema::hasTable('MHakAkses')
            || ! Schema::hasColumn('MPengguna', 'UserId')
        ) {
            return [];
        }

        return DB::table('MPengguna as p')
            ->join('MPeran as r', 'r.Id', '=', 'p.IdPeran')
            ->join('MPeranHakAkses as pr', 'pr.IdPeran', '=', 'r.Id')
            ->join('MHakAkses as h', 'h.Id', '=', 'pr.IdHakAkses')
            ->where(function ($query): void {
                $query
                    ->where('p.UserId', $this->getKey())
                    ->orWhere('p.Email', $this->email);
            })
            ->where('p.NonAktif', false)
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
        if (
            ! Schema::hasTable('MPengguna')
            || ! Schema::hasColumn('MPengguna', 'UserId')
            || ! Schema::hasColumn('MPengguna', 'FotoProfilPath')
        ) {
            return null;
        }

        $path = DB::table('MPengguna')
            ->where(function ($query): void {
                $query
                    ->where('UserId', $this->getKey())
                    ->orWhere('Email', $this->email);
            })
            ->where('NonAktif', false)
            ->value('FotoProfilPath');

        return $path ? route('public-storage.show', ['path' => ltrim((string) $path, '/')]) : null;
    }
}
