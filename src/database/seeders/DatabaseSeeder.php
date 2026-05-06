<?php

namespace Database\Seeders;

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
        if (! Schema::hasTable('MPeran') || ! Schema::hasTable('MPengguna')) {
            return;
        }

        $this->seedRoles();
        $this->seedPermissions();

        $peranAdmin = DB::table('MPeran')->where('KodePeran', 'ADMIN')->first();

        $penggunaDefaults = [
            'IdPeran' => $peranAdmin->Id,
            'NamaPengguna' => 'Admin VPoint Care',
            'Email' => 'mrthx.89@gmail.com',
            'Password' => Hash::make('Ell1t3s3rv'),
            'NonAktif' => false,
            'EmailTerverifikasiPada' => now(),
            'TglEdit' => now(),
        ];

        DB::table('MPengguna')->updateOrInsert([
            'Email' => 'mrthx.89@gmail.com',
        ], $penggunaDefaults);
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

        $localizedPermissions = AccessPermissions::localizedDefinitions();
        $hasLocalizedColumns = Schema::hasColumn('MHakAkses', 'NamaHakAksesId');

        foreach (AccessPermissions::definitions('id') as $code => $permission) {
            $localizedPermission = $localizedPermissions[$code] ?? null;
            $permissionData = [
                'NamaHakAkses' => $permission['label'],
                'Modul' => $permission['module'],
                'Keterangan' => $permission['description'],
                'NonAktif' => false,
                'TglEdit' => now(),
            ];

            if ($hasLocalizedColumns) {
                $permissionData += [
                    'NamaHakAksesId' => $localizedPermission['label_id'] ?? $permission['label'],
                    'NamaHakAksesEn' => $localizedPermission['label_en'] ?? $permission['label'],
                    'ModulId' => $localizedPermission['module_id'] ?? $permission['module'],
                    'ModulEn' => $localizedPermission['module_en'] ?? $permission['module'],
                    'KeteranganId' => $localizedPermission['description_id'] ?? $permission['description'],
                    'KeteranganEn' => $localizedPermission['description_en'] ?? $permission['description'],
                ];
            }

            DB::table('MHakAkses')->updateOrInsert([
                'KodeHakAkses' => $code,
            ], $permissionData);
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