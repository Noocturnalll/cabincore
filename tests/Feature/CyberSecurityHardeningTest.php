<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Auth\Login;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CyberSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveUser(string $nik = 'CYBER1', string $password = 'Secret123'): User
    {
        Role::findOrCreate(RoleHelper::SUPER_ADMIN, 'web');
        $user = User::factory()->create([
            'nik' => $nik,
            'password' => bcrypt($password),
            'status' => 'active',
            'is_default_password' => false,
        ]);
        $user->assignRole(RoleHelper::SUPER_ADMIN);

        return $user;
    }

    public function test_login_records_successful_audit_log_and_clears_throttle(): void
    {
        $user = $this->createActiveUser('SEC_OK_1', 'password123');

        Livewire::test(Login::class)
            ->set('nik', 'SEC_OK_1')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $log = LoginHistory::latest()->first();
        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('success', $log->status);
    }

    public function test_login_records_failed_audit_log_on_wrong_password(): void
    {
        $user = $this->createActiveUser('SEC_FAIL_1', 'correct_pw');

        Livewire::test(Login::class)
            ->set('nik', 'SEC_FAIL_1')
            ->set('password', 'wrong_pw')
            ->call('login')
            ->assertHasErrors('nik');

        $this->assertGuest();

        $log = LoginHistory::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('failed', $log->status);
    }

    public function test_account_locks_out_after_five_failed_attempts(): void
    {
        $this->createActiveUser('BRUTE_USER', 'real_password');

        $component = Livewire::test(Login::class)->set('nik', 'BRUTE_USER');

        for ($i = 1; $i <= 5; $i++) {
            $component->set('password', "bad_try_{$i}")->call('login')->assertHasErrors('nik');
        }

        // 6th attempt with valid password must still fail due to account throttle
        $component->set('password', 'real_password')->call('login')->assertHasErrors('nik');
        $this->assertGuest();
    }

    public function test_ip_rate_limiting_blocks_password_spraying_across_multiple_accounts(): void
    {
        $component = Livewire::test(Login::class);

        // Spray 15 different random NIKs from the same IP
        for ($i = 1; $i <= 15; $i++) {
            $component->set('nik', "SPRAY_USER_{$i}")->set('password', 'Pass123')->call('login');
        }

        // 16th attempt hits IP rate limit
        $component->set('nik', 'SPRAY_USER_16')->set('password', 'Pass123')
            ->call('login')
            ->assertHasErrors('nik')
            ->assertSee('Terlalu banyak request login dari IP Anda');
    }

    public function test_inactive_account_rejected_and_logs_failed_audit(): void
    {
        $user = $this->createActiveUser('INACTIVE_1', 'password123');
        $user->update(['status' => 'inactive']);

        Livewire::test(Login::class)
            ->set('nik', 'INACTIVE_1')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors('nik');

        $this->assertGuest();

        $log = LoginHistory::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('failed', $log->status);
    }
}
