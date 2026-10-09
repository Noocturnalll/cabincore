<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Accounts are created by an administrator; nobody can sign themselves up. */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_registration_is_closed(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(404);

        $this->assertGuest();
        $this->assertSame(0, User::where('email', 'intruder@example.com')->count());
    }
}
