<?php

namespace Database\Seeders;

use App\Support\AccessPermissions;
use App\Support\NavigationHelper;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! Schema::hasTable('MPeran') || ! Schema::hasTable('MPengguna')) {
            return;
        }

        $this->seedRoles();
        $this->seedPermissions();

        NavigationHelper::flush();

        $peranAdmin = DB::table('MPeran')->where('KodePeran', 'ADMIN')->first();

        if (! $peranAdmin) {
            throw new RuntimeException(__('ui.seeders.admin_role_not_found'));
        }

        $email = 'mrthx.89@gmail.com';
        $exists = DB::table('MPengguna')
            ->where('Email', $email)
            ->exists();

        if (! $exists) {
            DB::table('MPengguna')->insert([
                'IdPeran' => $peranAdmin->Id,
                'NamaPengguna' => 'Admin VPoint Care',
                'Email' => $email,
                'Password' => Hash::make('Ell1t3s3rv'),
                'NonAktif' => false,
                'EmailTerverifikasiPada' => now(),
                'TglEdit' => now(),
            ]);
        }
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
        $hasSidebarColumns = Schema::hasColumn('MHakAkses', 'IdHakAkses')
            && Schema::hasColumn('MHakAkses', 'SortOrder')
            && Schema::hasColumn('MHakAkses', 'IconString');
        $sidebarGroups = $hasSidebarColumns ? $this->seedSidebarGroups() : [];
        $sidebarMenus = AccessPermissions::sidebarMenus();
        $permissionGroups = AccessPermissions::permissionSidebarGroups();

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
                $groupKey = $sidebarMenus[$code]['group'] ?? $permissionGroups[$code] ?? null;
                $group = $groupKey ? AccessPermissions::sidebarGroups()[$groupKey] ?? null : null;

                $permissionData += [
                    'NamaHakAksesId' => $localizedPermission['label_id'] ?? $permission['label'],
                    'NamaHakAksesEn' => $localizedPermission['label_en'] ?? $permission['label'],
                    'ModulId' => $group['label_id'] ?? $localizedPermission['module_id'] ?? $permission['module'],
                    'ModulEn' => $group['label_en'] ?? $localizedPermission['module_en'] ?? $permission['module'],
                    'KeteranganId' => $localizedPermission['description_id'] ?? $permission['description'],
                    'KeteranganEn' => $localizedPermission['description_en'] ?? $permission['description'],
                ];
            }

            if ($hasSidebarColumns) {
                $menu = $sidebarMenus[$code] ?? null;
                $groupKey = $menu['group'] ?? $permissionGroups[$code] ?? null;

                $permissionData += [
                    'IdHakAkses' => $groupKey ? $sidebarGroups[$groupKey] ?? null : null,
                    'SortOrder' => $menu['sort'] ?? null,
                    'IconString' => $menu['icon'] ?? null,
                ];
            }

            DB::table('MHakAkses')->updateOrInsert([
                'KodeHakAkses' => $code,
            ], $permissionData);
        }

        if ($hasSidebarColumns) {
            $this->seedSidebarMenuRows($sidebarGroups);
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

    /**
     * @return array<string, string>
     */
    private function seedSidebarGroups(): array
    {
        $groupIds = [];
        $groups = AccessPermissions::sidebarGroups();
        $memberCodes = $this->sidebarMemberCodesByGroup();

        foreach ($groups as $key => $group) {
            $id = $this->findSidebarGroupId($group['label_id'], $memberCodes[$key] ?? []);

            if (! $id) {
                $id = (string) Str::uuid();

                DB::table('MHakAkses')->insert([
                    'Id' => $id,
                    'IdHakAkses' => null,
                    'KodeHakAkses' => null,
                    'NamaHakAkses' => $group['label_id'],
                    'NamaHakAksesId' => $group['label_id'],
                    'NamaHakAksesEn' => $group['label_en'],
                    'Modul' => $group['label_id'],
                    'ModulId' => $group['label_id'],
                    'ModulEn' => $group['label_en'],
                    'Keterangan' => $group['description_id'],
                    'KeteranganId' => $group['description_id'],
                    'KeteranganEn' => $group['description_en'],
                    'SortOrder' => $group['sort'],
                    'IconString' => $group['icon'],
                    'NonAktif' => false,
                    'TglEdit' => now(),
                ]);
            } else {
                $existingGroup = DB::table('MHakAkses')->where('Id', $id)->first();

                $iconString = $this->filledValue($existingGroup->IconString ?? null, $group['icon']);

                if (($existingGroup->IconString ?? null) === 'heroicon-o-squares-2x2' && $key === 'master_data') {
                    $iconString = $group['icon'];
                }

                DB::table('MHakAkses')
                    ->where('Id', $id)
                    ->update([
                        'IdHakAkses' => null,
                        'KodeHakAkses' => null,
                        'NamaHakAkses' => $this->filledValue($existingGroup->NamaHakAkses ?? null, $group['label_id']),
                        'NamaHakAksesId' => $this->filledValue($existingGroup->NamaHakAksesId ?? null, $group['label_id']),
                        'NamaHakAksesEn' => $this->filledValue($existingGroup->NamaHakAksesEn ?? null, $group['label_en']),
                        'Modul' => $this->filledValue($existingGroup->Modul ?? null, $group['label_id']),
                        'ModulId' => $this->filledValue($existingGroup->ModulId ?? null, $group['label_id']),
                        'ModulEn' => $this->filledValue($existingGroup->ModulEn ?? null, $group['label_en']),
                        'Keterangan' => $this->filledValue($existingGroup->Keterangan ?? null, $group['description_id']),
                        'KeteranganId' => $this->filledValue($existingGroup->KeteranganId ?? null, $group['description_id']),
                        'KeteranganEn' => $this->filledValue($existingGroup->KeteranganEn ?? null, $group['description_en']),
                        'SortOrder' => $existingGroup->SortOrder ?? $group['sort'],
                        'IconString' => $iconString,
                        'TglEdit' => now(),
                    ]);
            }

            $groupIds[$key] = $id;
        }

        return $groupIds;
    }

    /**
     * @param  array<string, string>  $groupIds
     */
    private function seedSidebarMenuRows(array $groupIds): void
    {
        foreach (AccessPermissions::sidebarMenus() as $code => $menu) {
            if (in_array($code, AccessPermissions::codes(), true)) {
                continue;
            }

            $group = $menu['group'] ? AccessPermissions::sidebarGroups()[$menu['group']] ?? null : null;

            DB::table('MHakAkses')->updateOrInsert([
                'KodeHakAkses' => $code,
            ], [
                'IdHakAkses' => $menu['group'] ? $groupIds[$menu['group']] ?? null : null,
                'NamaHakAkses' => $menu['label_id'],
                'NamaHakAksesId' => $menu['label_id'],
                'NamaHakAksesEn' => $menu['label_en'],
                'Modul' => $group['label_id'] ?? $menu['label_id'],
                'ModulId' => $group['label_id'] ?? $menu['label_id'],
                'ModulEn' => $group['label_en'] ?? $menu['label_en'],
                'Keterangan' => $menu['description_id'],
                'KeteranganId' => $menu['description_id'],
                'KeteranganEn' => $menu['description_en'],
                'SortOrder' => $menu['sort'],
                'IconString' => $menu['icon'],
                'NonAktif' => false,
                'TglEdit' => now(),
            ]);
        }
    }

    /**
     * @param  array<int, string>  $memberCodes
     */
    private function findSidebarGroupId(string $labelId, array $memberCodes): ?string
    {
        if ($memberCodes !== []) {
            $id = DB::table('MHakAkses as child')
                ->join('MHakAkses as parent', 'parent.Id', '=', 'child.IdHakAkses')
                ->whereIn('child.KodeHakAkses', $memberCodes)
                ->value('parent.Id');

            if ($id) {
                return (string) $id;
            }
        }

        $id = DB::table('MHakAkses')
            ->whereNull('KodeHakAkses')
            ->whereNull('IdHakAkses')
            ->where(function ($query) use ($labelId): void {
                $query->where('NamaHakAksesId', $labelId)
                    ->orWhere('NamaHakAkses', $labelId)
                    ->orWhere('ModulId', $labelId)
                    ->orWhere('Modul', $labelId);
            })
            ->value('Id');

        return $id ? (string) $id : null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function sidebarMemberCodesByGroup(): array
    {
        $members = [];

        foreach (AccessPermissions::sidebarMenus() as $code => $menu) {
            if ($menu['group']) {
                $members[$menu['group']][] = $code;
            }
        }

        foreach (AccessPermissions::permissionSidebarGroups() as $code => $group) {
            $members[$group][] = $code;
        }

        return $members;
    }

    private function filledValue(mixed $value, mixed $fallback): mixed
    {
        return $value === null || (is_string($value) && trim($value) === '')
            ? $fallback
            : $value;
    }
}
