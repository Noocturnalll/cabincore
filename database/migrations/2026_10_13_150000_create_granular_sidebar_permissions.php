<?php

use App\Helpers\RoleHelper;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $newPermissions = [
            // Cabin Maintenance
            'menu.cabin.dja',
            'menu.cabin.cml',
            'menu.cabin.nsrdi',
            'menu.cabin.dmi',
            'menu.cabin.wo',
            'menu.cabin.stretcher',
            'menu.cabin.dbi',
            'menu.cabin.daily_report',
            'menu.nsrdi.overdue',
            'menu.nsrdi.no_spare',
            'menu.nsrdi.pivot',

            // Painting
            'menu.painting.nsrdi',
            'menu.painting.daily_report',

            // Cleaning
            'menu.cleaning.hub',
            'menu.cleaning.daily_report',
            'menu.cleaning.sync',

            // Supporting & Tools
            'menu.tools_equipment',

            // ICT
            'menu.ict.pi',
            'menu.ict.tbd',

            // Capacity
            'menu.capacity.index',
            'menu.capacity.rotation',
            'menu.capacity.movement',

            // Operational
            'menu.operational.verification',
            'menu.operational.shift_recap',

            // Reports
            'reports.summary.view',
            'reports.kpi.view',
            'reports.document_accuracy.view',
            'reports.lgt.view',
            'reports.manpower.view',
            'reports.executive.view',

            // Master Data
            'master.airports.view',
            'master.aircraft.view',
            'master.categories.view',
            'master.divisions.view',
            'master.positions.view',
            'master.capacity.view',
            'master.system.view',

            // Security
            'security.users.view',
            'security.roles.view',
            'security.audit.view',

            // Documents & Profile
            'documents.view',
            'documents.master.view',
            'profile.view',
        ];

        foreach ($newPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Assign to existing roles based on their domain
        $superAdmin = Role::where('name', RoleHelper::SUPER_ADMIN)->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(Permission::all());
        }

        $cabinRoles = [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, RoleHelper::PIC_CBM, ...RoleHelper::COD_DESK];
        foreach ($cabinRoles as $roleName) {
            if ($r = Role::where('name', $roleName)->first()) {
                $r->givePermissionTo([
                    'menu.cabin.dja',
                    'menu.cabin.cml',
                    'menu.cabin.nsrdi',
                    'menu.cabin.dmi',
                    'menu.cabin.wo',
                    'menu.cabin.stretcher',
                    'menu.cabin.dbi',
                    'menu.cabin.daily_report',
                    'menu.nsrdi.overdue',
                    'menu.nsrdi.no_spare',
                    'menu.nsrdi.pivot',
                ]);
            }
        }

        if ($r = Role::where('name', RoleHelper::PIC_PAINTING)->first()) {
            $r->givePermissionTo([
                'menu.painting.nsrdi',
                'menu.painting.daily_report',
                'menu.nsrdi.overdue',
                'menu.nsrdi.no_spare',
                'menu.nsrdi.pivot',
            ]);
        }

        $cleaningRoles = [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, RoleHelper::PIC_AIEC, RoleHelper::ADMIN_AIEC];
        foreach ($cleaningRoles as $roleName) {
            if ($r = Role::where('name', $roleName)->first()) {
                $r->givePermissionTo([
                    'menu.cleaning.hub',
                    'menu.cleaning.daily_report',
                    'menu.cleaning.sync',
                ]);
            }
        }

        $ictRoles = [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, RoleHelper::PIC_CBM, ...RoleHelper::COD_DESK, RoleHelper::ADMIN_ICT];
        foreach ($ictRoles as $roleName) {
            if ($r = Role::where('name', $roleName)->first()) {
                $r->givePermissionTo(['menu.ict.pi', 'menu.ict.tbd']);
            }
        }

        $capacityRoles = [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, RoleHelper::PIC_CBM, ...RoleHelper::COD_DESK, RoleHelper::PIC_AIEC, RoleHelper::DEPUTY];
        foreach ($capacityRoles as $roleName) {
            if ($r = Role::where('name', $roleName)->first()) {
                $r->givePermissionTo(['menu.capacity.index', 'menu.capacity.rotation', 'menu.capacity.movement']);
            }
        }

        $analyticsRoles = [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, ...RoleHelper::COD_DESK, ...RoleHelper::ALL_PIC, RoleHelper::DEPUTY, RoleHelper::ADMIN_HC];
        foreach ($analyticsRoles as $roleName) {
            if ($r = Role::where('name', $roleName)->first()) {
                $r->givePermissionTo(['reports.summary.view', 'reports.kpi.view']);
            }
        }

        if ($r = Role::where('name', RoleHelper::MANAGER)->first()) {
            $r->givePermissionTo(['reports.executive.view', 'security.audit.view']);
        }

        $opRoles = [RoleHelper::ADMIN_CGK, ...RoleHelper::ALL_PIC];
        foreach ($opRoles as $roleName) {
            if ($r = Role::where('name', $roleName)->first()) {
                $r->givePermissionTo(['menu.operational.verification', 'menu.tools_equipment']);
            }
        }

        if ($r = Role::where('name', RoleHelper::ADMIN_CGK)->first()) {
            $r->givePermissionTo(['menu.operational.shift_recap']);
        }

        if ($r = Role::where('name', RoleHelper::DEPUTY)->first()) {
            $r->givePermissionTo(['master.airports.view', 'master.aircraft.view', 'master.capacity.view']);
        }

        foreach (Role::all() as $role) {
            $role->givePermissionTo(['documents.view', 'profile.view']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe down
    }
};
