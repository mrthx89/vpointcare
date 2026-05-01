<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserPenggunaSyncService
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public function syncFromUser(User $user, array $overrides = []): void
    {
        if (! $this->canSync()) {
            return;
        }

        $row = $this->penggunaForUser($user);
        $idPeran = $overrides['IdPeran'] ?? $row->IdPeran ?? $this->defaultRoleId();

        if (! $idPeran) {
            return;
        }

        $data = [
            'UserId' => $user->getKey(),
            'IdPeran' => $idPeran,
            'NamaPengguna' => $overrides['NamaPengguna'] ?? $user->name,
            'Email' => $user->email,
            'NonAktif' => $this->isInactiveUser($user),
            'TglEdit' => now(),
        ];

        if (array_key_exists('NomorWhatsappInternal', $overrides)) {
            $data['NomorWhatsappInternal'] = $overrides['NomorWhatsappInternal'];
        }

        if (array_key_exists('Jabatan', $overrides)) {
            $data['Jabatan'] = $overrides['Jabatan'];
        }

        if (array_key_exists('FotoProfilPath', $overrides) && Schema::hasColumn('MPengguna', 'FotoProfilPath')) {
            $data['FotoProfilPath'] = $overrides['FotoProfilPath'];
        }

        if ($user->password) {
            $data['Password'] = $user->password;
        }

        if ($row) {
            DB::table('MPengguna')->where('Id', $row->Id)->update($data);

            return;
        }

        DB::table('MPengguna')->insert(array_merge($data, [
            'Id' => (string) Str::orderedUuid(),
            'Password' => $user->password ?: Hash::make(Str::random(32)),
            'TglBuat' => now(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $penggunaData
     */
    public function syncFromPengguna(array $penggunaData): ?User
    {
        if (! Schema::hasTable('users')) {
            return null;
        }

        $email = (string) ($penggunaData['Email'] ?? '');

        if ($email === '') {
            return null;
        }

        $userId = $penggunaData['UserId'] ?? null;
        $user = $userId ? User::query()->find($userId) : User::query()->where('email', $email)->first();

        $password = (string) ($penggunaData['Password'] ?? '');
        $data = [
            'name' => (string) ($penggunaData['NamaPengguna'] ?? $email),
            'email' => $email,
            'status' => (bool) ($penggunaData['NonAktif'] ?? false) ? User::STATUS_BLOCKED : User::STATUS_APPROVED,
            'approved_at' => (bool) ($penggunaData['NonAktif'] ?? false) ? null : now(),
            'blocked_at' => (bool) ($penggunaData['NonAktif'] ?? false) ? now() : null,
        ];

        if ($password !== '') {
            $data['password'] = str_starts_with($password, '$2y$') || str_starts_with($password, '$argon')
                ? $password
                : Hash::make($password);
        }

        if ($user) {
            $user->forceFill($data)->save();
        } else {
            $user = User::query()->create(array_merge($data, [
                'password' => $data['password'] ?? Hash::make(Str::random(32)),
            ]));
        }

        if ($this->canSync()) {
            DB::table('MPengguna')
                ->where('Email', $email)
                ->update([
                    'UserId' => $user->getKey(),
                    'Password' => $user->password,
                    'TglEdit' => now(),
                ]);
        }

        return $user;
    }

    private function canSync(): bool
    {
        return Schema::hasTable('MPengguna')
            && Schema::hasTable('MPeran')
            && Schema::hasColumn('MPengguna', 'UserId');
    }

    private function penggunaForUser(User $user): ?object
    {
        $query = DB::table('MPengguna');

        return $query
            ->where('UserId', $user->getKey())
            ->orWhere('Email', $user->email)
            ->first();
    }

    private function defaultRoleId(): ?string
    {
        return DB::table('MPeran')
            ->where('KodePeran', 'CS')
            ->value('Id')
            ?? DB::table('MPeran')->where('KodePeran', 'ADMIN')->value('Id')
            ?? DB::table('MPeran')->orderBy('NamaPeran')->value('Id');
    }

    private function isInactiveUser(User $user): bool
    {
        return $user->status !== User::STATUS_APPROVED;
    }
}
