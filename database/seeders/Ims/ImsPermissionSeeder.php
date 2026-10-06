<?php

namespace Database\Seeders\Ims;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ImsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'ims.catalog.view',
            'ims.item.manage',
            'ims.master.manage',
            'ims.request.create',
            'ims.request.view_own',
            'ims.request.view_all',
            'ims.approval.view',
            'ims.approval.act',
            'ims.stock.in',
            'ims.stock.adjust',
            'ims.stock.transfer',
            'ims.stock.handover',
            'ims.repair.request',
            'ims.repair.manage',
            'ims.repair.back_stage',
            'ims.report.view',
            'ims.report.export',
            'ims.user.manage',
            'ims.audit.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        
        // Roles (creating if they don't exist, though typically they exist in the app)
        $admin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin->givePermissionTo($permissions);
    }
}
