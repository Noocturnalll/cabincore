<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Roles\Index as RolesIndex;
use App\Models\User;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);

        $this->superAdmin = User::factory()->create([
            'status' => 'active',
            'is_default_password' => false,
        ]);
        $this->superAdmin->assignRole(RoleHelper::SUPER_ADMIN);

        $this->manager = User::factory()->create([
            'status' => 'active',
            'is_default_password' => false,
        ]);
        $this->manager->assignRole(RoleHelper::MANAGER);
    }

    public function test_super_admin_can_open_roles_page(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertSee('Peran & Hak Akses');
    }

    public function test_non_super_admin_cannot_open_roles_page(): void
    {
        $this->actingAs($this->manager)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_new_role(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(RolesIndex::class)
            ->set('roleName', 'Quality Inspector')
            ->call('saveRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'Quality Inspector',
            'guard_name' => 'web',
        ]);
    }

    public function test_super_admin_can_sync_permissions_matrix(): void
    {
        $role = Role::create(['name' => 'Field Technician', 'guard_name' => 'web']);

        Livewire::actingAs($this->superAdmin)
            ->test(RolesIndex::class)
            ->call('openPermissionsMatrix', $role->id)
            ->set('selectedPermissions.menu.production', true)
            ->set('selectedPermissions.compliance.view', true)
            ->call('savePermissions')
            ->assertHasNoErrors();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('menu.production'));
        $this->assertTrue($role->hasPermissionTo('compliance.view'));
        $this->assertFalse($role->hasPermissionTo('menu.cleaning'));
    }

    public function test_cannot_delete_super_admin_role(): void
    {
        $superAdminRole = Role::findByName(RoleHelper::SUPER_ADMIN);

        Livewire::actingAs($this->superAdmin)
            ->test(RolesIndex::class)
            ->call('deleteRole', $superAdminRole->id);

        $this->assertDatabaseHas('roles', [
            'name' => RoleHelper::SUPER_ADMIN,
        ]);
    }
}
