<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_sends_visitors_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_protected_pages_send_guests_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/modules/wo')->assertRedirect('/login');
    }
}
