<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * hr.view      see the employee database (own division only, unless hr.view_all)
 * hr.manage    add / edit / delete employees (own division only, unless hr.view_all)
 * hr.view_all  all divisions
 * Give hr.view / hr.manage to a division admin role from User Management; Super Admin gets everything.
 */
class HrPermissionSeeder extends Seeder
{
    public const PERMISSIONS = ['hr.view', 'hr.manage', 'hr.view_all'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web'])->givePermissionTo(self::PERMISSIONS);
    }
}
