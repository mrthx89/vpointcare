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
use Illuminate\Support\Facades\Storage;

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

        return $path ? Storage::disk('public')->url((string) $path) : null;
    }
}
