<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\NsrdiOverdue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardOverdueTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_operational_tab_opens_with_open_overdue_documents_present(): void
    {
        // the overdue table keeps the airline in `operator`; the dashboard once selected a column that does not exist
        NsrdiOverdue::create(['operator' => 'Lion Air', 'aircraft_registration' => 'PK-ABC', 'nsrdi_number' => 'N-1', 'status' => 'Open', 'due_date' => now()->subDays(3)->toDateString()]);
        NsrdiOverdue::create(['operator' => 'Batik Air', 'aircraft_registration' => 'PK-DEF', 'nsrdi_number' => 'N-2', 'status' => 'Open', 'due_date' => now()->subDay()->toDateString()]);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole(Role::findOrCreate('Super Admin'));
        $this->actingAs($user);

        $this->get(route('dashboard', ['t' => 'ops']))->assertOk();
        Livewire::test(Dashboard::class)->assertOk();
    }
}
