<?php

namespace App\Support;

use App\Models\User;

class FilamentAccess
{
    public static function can(string $permission): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasPermissionCode($permission);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public static function canAny(array $permissions): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasAnyPermissionCode($permissions);
    }

    public static function canViewMasterCustomer(): bool
    {
        return self::can(AccessPermissions::MASTER_CUSTOMER_VIEW);
    }

    public static function canManageMasterCustomer(): bool
    {
        return self::can(AccessPermissions::MASTER_CUSTOMER_MANAGE);
    }

    public static function canManageUsers(): bool
    {
        return self::can(AccessPermissions::USER_MANAGE);
    }
}
