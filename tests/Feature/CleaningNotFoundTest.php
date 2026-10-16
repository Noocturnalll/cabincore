<?php

namespace Tests\Feature;

use App\Livewire\Modules\AircraftCleaning\GeneralInterior;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CleaningNotFoundTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole(Role::findOrCreate('Super Admin'));

        return $u;
    }

    public function test_old_cleaning_addresses_land_on_the_hub_instead_of_a_404(): void
    {
        $this->actingAs($this->admin());

        $this->get('/modules/aircraft-cleaning/transit')->assertRedirect('/modules/aircraft-cleaning?tipe=transit');
        $this->get('/modules/aircraft-cleaning/dci')->assertRedirect('/modules/aircraft-cleaning?tipe=dci');
        $this->get('/modules/cleaning')->assertRedirect('/modules/aircraft-cleaning');
        $this->get('/modules/aircraft-cleaning?tipe=gce')->assertOk();
    }

    public function test_a_record_replaced_by_an_import_gives_a_message_not_a_404(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(GeneralInterior::class)
            ->call('edit', 987654)
            ->assertOk()
            ->assertDispatched('notify')
            ->call('deleteConfirm', 987654)
            ->assertOk()
            ->assertSet('deleteId', null);
    }
}
