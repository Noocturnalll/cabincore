<?php

namespace Tests\Feature;

use App\Livewire\Profile\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk();
    }

    public function test_profile_page_needs_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_password_can_be_changed_with_the_current_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Index::class)
            ->set('current_password', 'password')
            ->set('password', 'new-secret-123')
            ->set('password_confirmation', 'new-secret-123')
            ->call('changePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-123', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'Ganti Password']);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Index::class)
            ->set('current_password', 'not-my-password')
            ->set('password', 'new-secret-123')
            ->set('password_confirmation', 'new-secret-123')
            ->call('changePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_new_password_must_be_confirmed_and_long_enough(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Index::class)
            ->set('current_password', 'password')
            ->set('password', 'short')
            ->set('password_confirmation', 'different')
            ->call('changePassword')
            ->assertHasErrors(['password']);
    }
}
