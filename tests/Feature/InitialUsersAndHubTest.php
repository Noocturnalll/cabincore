<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\DashboardHub;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\User;
use Database\Seeders\InitialUsersSeeder;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class InitialUsersAndHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_the_eight_accounts_with_the_default_password_and_forced_reset(): void
    {
        $this->seed(InitialUsersSeeder::class);

        $this->assertSame(8, User::whereIn('nik', ['83119910', '83099398', '145340', '83040043', '53030503', '83075251', '221927', '212223'])->count());

        $owner = User::where('nik', '212223')->firstOrFail();
        $this->assertTrue($owner->hasRole(RoleHelper::SUPER_ADMIN));
        $this->assertNotSame(User::where('nik', '221927')->value('id'), $owner->id, 'the COD account and the Super Admin account are two different logins');

        $cod = User::where('nik', '221927')->firstOrFail();
        $this->assertTrue($cod->hasRole(RoleHelper::COD));
        $this->assertTrue(Hash::check('Password123', $cod->password));
        $this->assertTrue((bool) $cod->is_default_password);

        $ahmad = User::where('nik', '83099398')->firstOrFail();
        $this->assertTrue($ahmad->hasRole(RoleHelper::PIC_SUPPORTING) && $ahmad->hasRole(RoleHelper::COD));
        $this->assertTrue(User::where('nik', '53030503')->firstOrFail()->hasRole(RoleHelper::MANAGER));

        // first login goes to the change-password form
        $this->actingAs($cod)->get('/dashboard')->assertRedirect(route('force-password-reset'));
    }

    public function test_running_the_seeder_again_does_not_reset_a_password_somebody_chose(): void
    {
        $this->seed(InitialUsersSeeder::class);
        $user = User::where('nik', '145340')->firstOrFail();
        $user->update(['password' => Hash::make('my-own-secret-1'), 'is_default_password' => false]);

        $this->seed(InitialUsersSeeder::class);

        $user->refresh();
        $this->assertTrue(Hash::check('my-own-secret-1', $user->password));
        $this->assertFalse((bool) $user->is_default_password);
        $this->assertSame(8, User::where('email', 'like', '%@bat.local')->count());
    }

    public function test_the_default_password_for_new_users_is_the_agreed_one(): void
    {
        $this->assertSame('Password123', UsersIndex::DEFAULT_PASSWORD);
    }

    public function test_one_dashboard_with_kpi_and_operational_tabs(): void
    {
        $this->seed(RegistryPermissionSeeder::class);
        $manager = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $manager->assignRole(RoleHelper::MANAGER);

        $this->actingAs($manager)->get('/dashboard')->assertOk()->assertSee('Ringkasan KPI')->assertSee('Operasional');

        Livewire::actingAs($manager)->test(DashboardHub::class)->assertSet('tab', 'kpi')
            ->call('setTab', 'ops')->assertSet('tab', 'ops')
            ->call('setTab', 'nonsense')->assertSet('tab', 'kpi');
    }

    public function test_a_user_without_kpi_access_only_gets_the_operational_view(): void
    {
        $this->seed(RegistryPermissionSeeder::class);
        $plain = User::factory()->create(['is_default_password' => false, 'status' => 'active']);

        Livewire::actingAs($plain)->test(DashboardHub::class)->assertSet('tab', 'ops')
            ->call('setTab', 'kpi')->assertSet('tab', 'ops')->assertDontSee('Ringkasan KPI');
    }
}
