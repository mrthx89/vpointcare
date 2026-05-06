<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Auth\UserPenggunaSyncService;
use App\Support\AccessPermissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::query()
        //     ->where('email', 'admin@vpointcare.local')
        //     ->delete();

        $user = User::query()->firstOrCreate([
            'email' => 'mrthx.89@gmail.com',
        ], [
            'name' => 'Admin VPoint Care',
            'password' => Hash::make('Ell1t3s3rv'),
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
            'blocked_at' => null,
        ]);

        if (! Schema::hasTable('MPeran') || ! Schema::hasTable('MPengguna')) {
            return;
        }

        $this->seedRoles();
        $this->seedPermissions();

        $peranAdmin = DB::table('MPeran')->where('KodePeran', 'ADMIN')->first();

        $penggunaDefaults = [
            'IdPeran' => $peranAdmin->Id,
            'NamaPengguna' => 'Admin VPoint Care',
            'Password' => Hash::make('Ell1t3s3rv'),
            'NonAktif' => false,
            'TglEdit' => now(),
        ];

        if (Schema::hasColumn('MPengguna', 'UserId')) {
            $penggunaDefaults['UserId'] = $user->getKey();
        }

        DB::table('MPengguna')->updateOrInsert([
            'Email' => 'mrthx.89@gmail.com',
        ], $penggunaDefaults);

        app(UserPenggunaSyncService::class)->syncFromUser($user, [
            'IdPeran' => $peranAdmin->Id,
            'NamaPengguna' => 'Admin VPoint Care',
        ]);
    }

    private function seedRoles(): void
    {
        foreach (AccessPermissions::defaultRoles() as $code => $role) {
            DB::table('MPeran')->updateOrInsert([
                'KodePeran' => $code,
            ], [
                'NamaPeran' => $role['name'],
                'Keterangan' => $role['description'],
                'NonAktif' => false,
                'TglEdit' => now(),
            ]);
        }
    }

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('MHakAkses') || ! Schema::hasTable('MPeranHakAkses')) {
            return;
        }

        foreach (AccessPermissions::definitions() as $code => $permission) {
            DB::table('MHakAkses')->updateOrInsert([
                'KodeHakAkses' => $code,
            ], [
                'NamaHakAkses' => $permission['label'],
                'Modul' => $permission['module'],
                'Keterangan' => $permission['description'],
                'NonAktif' => false,
                'TglEdit' => now(),
            ]);
        }

        $roles = DB::table('MPeran')
            ->whereIn('KodePeran', array_keys(AccessPermissions::defaultRolePermissions()))
            ->pluck('Id', 'KodePeran');

        $permissions = DB::table('MHakAkses')
            ->whereIn('KodeHakAkses', AccessPermissions::codes())
            ->pluck('Id', 'KodeHakAkses');

        foreach (AccessPermissions::defaultRolePermissions() as $roleCode => $permissionCodes) {
            $roleId = $roles[$roleCode] ?? null;

            if (! $roleId) {
                continue;
            }

            foreach ($permissionCodes as $permissionCode) {
                $permissionId = $permissions[$permissionCode] ?? null;

                if (! $permissionId) {
                    continue;
                }

                DB::table('MPeranHakAkses')->updateOrInsert([
                    'IdPeran' => $roleId,
                    'IdHakAkses' => $permissionId,
                ], [
                    'NonAktif' => false,
                    'TglEdit' => now(),
                ]);
            }
        }
    }
}
