<?php

namespace Database\Seeders;

use App\Helpers\RoleHelper;
use App\Models\Division;
use App\Models\Employee;
use App\Models\MasterEntry;
use App\Models\RegistryRecord;
use App\Models\User;
use App\Services\Master\MasterSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The access matrix. Creates the permissions, the five divisions and their roles, and decides what each role may
 * open. Safe to re-run: it only adds, except for the one-time clean-up of the old role / division names
 * (PIC Cabin -> PIC CBM, PIC AIC / PIC Irreg folded into AIEC / CBM, Team Irreg and AIC divisions retired).
 *
 * Divisions / PIC roles:  CBM (division "Cabin") | Painting | AIEC | Supporting | Finishing
 *
 * menu.* permissions decide which sidebar blocks - and the pages behind them - a role gets:
 *   menu.production  Cabin Maintenance: DJA, WO, DMI, CML, Daily Report, Laporan Leader, LGT (also COD)
 *   menu.painting    reduced block for Painting: NSRDI logs, Daily Report
 *   menu.cleaning    Aircraft Cleaning (all cleaning logs, daily report, sync)
 *   menu.nsrdi       NSRDI Management: overdue, no spare, pivot per AOC
 *   menu.ict         ICT findings
 *   menu.capacity    Capacity, Aircraft Rotation, AC Movement
 *   menu.inventory   Inventory (IMS); what can be done inside is decided by the ims.* permissions
 *   menu.analytics   Analitik (summary, KPI pages)
 */
class RegistryPermissionSeeder extends Seeder
{
    /** menu permission => roles that get it (Super Admin always gets everything through Gate::before and below) */
    private const MENUS = [
        'menu.production' => [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, RoleHelper::PIC_CBM, ...RoleHelper::COD_DESK],
        'menu.painting' => [RoleHelper::PIC_PAINTING],
        'menu.cleaning' => [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, RoleHelper::PIC_AIEC],
        'menu.nsrdi' => [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, RoleHelper::PIC_CBM, ...RoleHelper::COD_DESK, RoleHelper::PIC_PAINTING],
        'menu.ict' => [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, RoleHelper::PIC_CBM, ...RoleHelper::COD_DESK],
        'menu.capacity' => [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, RoleHelper::PIC_CBM, ...RoleHelper::COD_DESK, RoleHelper::PIC_AIEC],
        'menu.inventory' => [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, ...RoleHelper::COD_DESK, ...RoleHelper::ALL_PIC],
        'menu.analytics' => [RoleHelper::MANAGER, RoleHelper::ADMIN_CGK, ...RoleHelper::COD_DESK, ...RoleHelper::ALL_PIC],
    ];

    /** IMS: everybody can look at the catalogue and ask for parts; the Supporting PIC runs the store; the Manager approves and reports. */
    private const IMS_REQUESTER = ['ims.catalog.view', 'ims.request.create', 'ims.request.view_own'];

    private const IMS_STORE = ['ims.item.manage', 'ims.request.view_all', 'ims.approval.view', 'ims.stock.in', 'ims.stock.adjust', 'ims.stock.transfer', 'ims.stock.handover', 'ims.repair.request', 'ims.repair.manage', 'ims.repair.back_stage', 'ims.report.view', 'ims.report.export'];

    private const IMS_COD = ['ims.request.view_all', 'ims.repair.request'];

    private const IMS_MANAGER = ['ims.catalog.view', 'ims.request.view_all', 'ims.request.view_own', 'ims.approval.view', 'ims.approval.act', 'ims.report.view', 'ims.report.export', 'ims.audit.view'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->restructure();

        $moduleKeys = array_keys(config('registry.modules'));
        $modulePerms = ['registry.view_all'];
        foreach ($moduleKeys as $key) {
            $modulePerms[] = "registry.$key.view";
            $modulePerms[] = "registry.$key.manage";
        }
        $masterView = [];
        $masterManage = [];
        foreach (array_keys(config('master.types')) as $type) {
            $masterView[] = "master.$type.view";
            $masterManage[] = "master.$type.manage";
        }
        // attendance: view = own scope, manage = import presensi; assets: assign = lend / take back; sources = Sumber Data page
        $featurePerms = ['attendance.view', 'attendance.manage', 'asset.view', 'asset.manage', 'asset.assign', 'sources.view', 'sources.sync', 'lgt.view', 'lgt.manage', 'lgt.plan'];
        $core = array_merge(HrPermissionSeeder::PERMISSIONS, ['leader.import', 'compliance.view', 'kpi.view'], $masterView, $masterManage, $featurePerms, array_keys(self::MENUS));

        $ims = array_values(array_unique(array_merge(self::IMS_REQUESTER, self::IMS_STORE, self::IMS_MANAGER, ['ims.master.manage', 'ims.user.manage', 'ims.audit.view'])));
        foreach (array_merge($modulePerms, $core, $ims) as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // what every working role does in its own scope (division / station): read the master lists, people, assets, presensi
        $own = array_values(array_diff($modulePerms, ['registry.view_all']));
        $common = array_merge($own, $masterView, ['hr.view', 'hr.manage', 'leader.import', 'compliance.view', 'kpi.view', 'attendance.view', 'asset.view', 'asset.manage', 'asset.assign', 'sources.view', 'lgt.view', 'lgt.manage']);

        $grants = [
            RoleHelper::SUPER_ADMIN => array_merge($modulePerms, $core, $ims),
            RoleHelper::MANAGER => array_merge($modulePerms, $core, self::IMS_MANAGER),
        ];
        foreach (array_merge(RoleHelper::ALL_PIC, [RoleHelper::ADMIN_CGK, ...RoleHelper::COD_DESK]) as $role) {
            $grants[$role] = array_merge($common, self::IMS_REQUESTER);
        }
        $grants[RoleHelper::PIC_SUPPORTING] = array_merge($grants[RoleHelper::PIC_SUPPORTING], self::IMS_STORE);
        // COD watches stock and requests, and takes in faulty parts from the field before they go to repair
        foreach (RoleHelper::COD_DESK as $cod) {
            $grants[$cod] = array_merge($grants[$cod], self::IMS_COD);
        }

        foreach ($grants as $role => $perms) {
            $r = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $r->givePermissionTo($perms);
            $r->givePermissionTo(array_keys(array_filter(self::MENUS, fn ($roles) => in_array($role, $roles, true))));
            // menu.* are the only permissions that are taken away again when the matrix changes
            $r->revokePermissionTo(array_values(array_filter(array_keys(self::MENUS), fn ($m) => ! in_array($role, self::MENUS[$m], true) && $role !== RoleHelper::SUPER_ADMIN)));
        }
        Role::findByName(RoleHelper::SUPER_ADMIN)->givePermissionTo(array_keys(self::MENUS));

        // the repair desk: COD files the form, the store / repair team accepts and works it (older runs gave these more widely)
        foreach (array_merge(RoleHelper::ALL_PIC, [RoleHelper::ADMIN_CGK, RoleHelper::MANAGER]) as $role) {
            if ($role !== RoleHelper::PIC_SUPPORTING && ($r = Role::where('name', $role)->first())) {
                $r->revokePermissionTo(['ims.repair.request']);
            }
        }
        // LGT: the COD desk drafts (plan), Finishing / CBM / AIEC fill in (manage)
        foreach (RoleHelper::COD_DESK as $role) {
            $r = Role::findByName($role);
            $r->revokePermissionTo(['ims.repair.manage', 'lgt.manage']);
            $r->givePermissionTo('lgt.plan');
        }
        foreach ([RoleHelper::PIC_PAINTING, RoleHelper::PIC_SUPPORTING] as $role) {
            Role::findByName($role)->revokePermissionTo('lgt.manage');
        }

        MasterSettings::flush();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /** Five divisions, one PIC role each; the old names are folded into them. */
    private function restructure(): void
    {
        $keep = ['Cabin', 'Painting', 'AIEC', 'Supporting', 'Finishing'];
        foreach ($keep as $name) {
            Division::withTrashed()->firstOrCreate(['name' => $name], ['status' => 'Aktif'])->forceFill(['status' => 'Aktif', 'deleted_at' => null])->save();
        }

        // old division -> the division that now owns its people, assets and records
        foreach (['AIC' => 'AIEC', 'Team Irreg' => 'Cabin'] as $old => $new) {
            $oldId = Division::where('name', $old)->value('id');
            $newId = Division::where('name', $new)->value('id');
            if ($oldId && $newId) {
                User::where('division_id', $oldId)->update(['division_id' => $newId]);
                Employee::where('division_id', $oldId)->update(['division_id' => $newId]);
                RegistryRecord::where('division_id', $oldId)->update(['division_id' => $newId]);
                DB::table('assets')->where('division_id', $oldId)->update(['division_id' => $newId]);
            }
        }
        Division::whereNotIn('name', $keep)->update(['status' => 'Nonaktif']);

        // the roster team of the new division, and Irreg is part of CBM now
        // (only once the team master is filled; until then the config defaults carry both)
        if (MasterEntry::where('type', 'team')->exists()) {
            MasterEntry::firstOrCreate(['type' => 'team', 'code' => 'FINISHING'], ['label' => 'Aircraft Finishing', 'attrs' => ['capacity' => 'Ya', 'division' => 'Finishing'], 'is_active' => true]);
            MasterEntry::where('type', 'team')->where('code', 'IRREG')->update(['attrs' => ['capacity' => 'Ya', 'division' => 'Cabin']]);
        }

        // roles: rename or fold the old ones
        if (($old = Role::where('name', 'PIC Cabin')->first()) && ! Role::where('name', 'PIC CBM')->exists()) {
            $old->update(['name' => 'PIC CBM']);
        }
        foreach (['PIC Cabin' => 'PIC CBM', 'PIC AIC' => 'PIC AIEC', 'PIC Irreg' => 'PIC CBM'] as $oldName => $newName) {
            $oldRole = Role::where('name', $oldName)->first();
            if (! $oldRole) {
                continue;
            }
            $target = Role::firstOrCreate(['name' => $newName, 'guard_name' => 'web']);
            foreach (User::role($oldName)->get() as $user) {
                $user->removeRole($oldName);
                $user->assignRole($target);
            }
            $oldRole->delete();
        }
        foreach (RoleHelper::ALL_PIC as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
