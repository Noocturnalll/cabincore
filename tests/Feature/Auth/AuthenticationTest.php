<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ForcePasswordReset;
use App\Livewire\Auth\Login;
use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_users_can_authenticate_with_their_id(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::test(Login::class)
            ->set('nik', $user->nik)->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::test(Login::class)
            ->set('nik', $user->nik)->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['nik']);

        $this->assertGuest();
    }

    public function test_a_disabled_account_cannot_sign_in_even_with_the_right_password(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        Livewire::test(Login::class)
            ->set('nik', $user->nik)->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['nik']);

        $this->assertGuest();
    }

    public function test_login_is_blocked_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $page = Livewire::test(Login::class)->set('nik', $user->nik)->set('password', 'nope');

        for ($i = 0; $i < 5; $i++) {
            $page->call('login');
        }
        // even the right password is refused while throttled
        $page->set('password', 'password')->call('login')->assertHasErrors(['nik']);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_user_with_default_password_is_forced_to_reset_password_on_first_login(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'is_default_password' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('force-password-reset'));
    }

    public function test_user_can_complete_forced_password_reset(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'is_default_password' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ForcePasswordReset::class)
            ->set('password', 'new-secure-password')
            ->set('password_confirmation', 'new-secure-password')
            ->call('updatePassword')
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse((bool) $user->is_default_password);
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
    }

    public function test_user_cannot_reuse_default_password_on_forced_reset(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'is_default_password' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ForcePasswordReset::class)
            ->set('password', Index::DEFAULT_PASSWORD)
            ->set('password_confirmation', Index::DEFAULT_PASSWORD)
            ->call('updatePassword')
            ->assertHasErrors(['password']);

        $user->refresh();
        $this->assertTrue((bool) $user->is_default_password);
    }

    public function test_user_can_logout_from_forced_reset_page(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'is_default_password' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ForcePasswordReset::class)
            ->call('logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
