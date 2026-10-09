<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Auth\Login;
use App\Livewire\Users\Index;
use App\Models\Airport;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, array $attrs = []): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create(array_merge(['is_default_password' => false, 'status' => 'active'], $attrs));
        $user->assignRole($role);

        return $user;
    }

    public function test_only_super_admin_can_open_the_page(): void
    {
        $pic = $this->userWithRole(RoleHelper::PIC_CABIN);
        $admin = $this->userWithRole(RoleHelper::SUPER_ADMIN);

        $this->actingAs($pic)->get('/users')->assertForbidden();
        $this->actingAs($admin)->get('/users')->assertOk();
    }

    public function test_livewire_actions_are_forbidden_for_non_admins(): void
    {
        $pic = $this->userWithRole(RoleHelper::PIC_CABIN);
        $victim = $this->userWithRole(RoleHelper::MANAGER);

        // The page itself is blocked, and so are the underlying endpoints
        Livewire::actingAs($pic)->test(Index::class)->assertForbidden();
        $this->assertTrue($victim->fresh()->hasRole(RoleHelper::MANAGER));
    }

    public function test_super_admin_can_create_a_user_with_default_password(): void
    {
        $admin = $this->userWithRole(RoleHelper::SUPER_ADMIN);
        Role::findOrCreate(RoleHelper::PIC_AIC, 'web');
        $position = Position::create(['name' => 'Teknisi', 'status' => 'Aktif']);
        Airport::create(['kode' => 'CGK', 'nama' => 'Soekarno-Hatta', 'kota' => 'Tangerang', 'status' => 'Aktif']);

        Livewire::actingAs($admin)->test(Index::class)
            ->call('create')
            ->set('nik', '  12345 ')
            ->set('name', 'Budi Santoso')
            ->set('email', ' BUDI@Example.com ')
            ->set('position_id', $position->id)
            ->set('station', 'CGK')
            ->set('role', RoleHelper::PIC_AIC)
            ->call('store')
            ->assertHasNoErrors();

        $user = User::where('nik', '12345')->firstOrFail();
        $this->assertSame('budi@example.com', $user->email);
        $this->assertEquals(1, $user->is_default_password);
        $this->assertTrue(Hash::check(Index::DEFAULT_PASSWORD, $user->password));
        $this->assertTrue($user->hasRole(RoleHelper::PIC_AIC));
    }

    public function test_unknown_role_or_station_is_rejected(): void
    {
        $admin = $this->userWithRole(RoleHelper::SUPER_ADMIN);
        $position = Position::create(['name' => 'Teknisi', 'status' => 'Aktif']);

        Livewire::actingAs($admin)->test(Index::class)
            ->call('create')
            ->set('nik', '777')->set('name', 'X')->set('email', 'x@example.com')
            ->set('position_id', $position->id)->set('station', 'ZZZ')->set('role', 'Root')
            ->call('store')
            ->assertHasErrors(['role', 'station']);
    }

    public function test_cannot_delete_or_demote_self_or_the_last_super_admin(): void
    {
        $admin = $this->userWithRole(RoleHelper::SUPER_ADMIN);
        $position = Position::create(['name' => 'Teknisi', 'status' => 'Aktif']);
        Role::findOrCreate(RoleHelper::MANAGER, 'web');

        $component = Livewire::actingAs($admin)->test(Index::class);

        $component->call('delete', $admin->id);
        $this->assertNotNull(User::find($admin->id));

        $component->call('edit', $admin->id)
            ->set('position_id', $position->id)
            ->set('role', RoleHelper::MANAGER)
            ->call('update')
            ->assertHasErrors('role');
        $this->assertTrue($admin->fresh()->hasRole(RoleHelper::SUPER_ADMIN));

        // A second admin can be removed, but not when it would leave zero active admins
        $other = $this->userWithRole(RoleHelper::SUPER_ADMIN);
        $component->call('delete', $other->id);
        $this->assertNull(User::find($other->id));
    }

    public function test_reset_password_marks_account_for_forced_change(): void
    {
        $admin = $this->userWithRole(RoleHelper::SUPER_ADMIN);
        $target = $this->userWithRole(RoleHelper::MANAGER);

        Livewire::actingAs($admin)->test(Index::class)->call('resetPassword', $target->id);

        $target->refresh();
        $this->assertEquals(1, $target->is_default_password);
        $this->assertTrue(Hash::check(Index::DEFAULT_PASSWORD, $target->password));
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $this->userWithRole(RoleHelper::MANAGER, ['status' => 'inactive', 'nik' => 'OFF1']);

        Livewire::test(Login::class)
            ->set('nik', 'OFF1')->set('password', 'password')
            ->call('login')
            ->assertHasErrors('nik')
            ->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_active_account_can_log_in(): void
    {
        $this->userWithRole(RoleHelper::MANAGER, ['nik' => 'ON1']);

        Livewire::test(Login::class)
            ->set('nik', 'ON1')->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $this->userWithRole(RoleHelper::MANAGER, ['nik' => 'RL1']);

        $login = Livewire::test(Login::class)->set('nik', 'RL1')->set('password', 'wrong');
        foreach (range(1, 5) as $i) {
            $login->call('login');
        }

        // Even the right password is refused while locked out
        $login->set('password', 'password')->call('login')->assertHasErrors('nik')->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_session_of_an_account_disabled_later_is_ended(): void
    {
        $user = $this->userWithRole(RoleHelper::MANAGER);
        $this->actingAs($user)->get('/profile')->assertOk();

        $user->update(['status' => 'inactive']);

        $this->actingAs($user->fresh())->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
