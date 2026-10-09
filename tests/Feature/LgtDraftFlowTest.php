<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\Lgt\Index;
use App\Models\LgtRecord;
use App\Models\User;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Admin COD drafts which aircraft are LGT and for how long; Finishing (and CBM / AIEC) fill in the jobs. */
class LgtDraftFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);
    }

    private function user(string $role, string $station = 'CGK'): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'station' => $station]);
        $u->assignRole($role);

        return $u;
    }

    public function test_admin_cod_drafts_a_line_and_finishing_is_told(): void
    {
        $admin = $this->user(RoleHelper::ADMIN_COD);
        $finishing = $this->user(RoleHelper::PIC_FINISHING);

        Livewire::actingAs($admin)->test(Index::class)
            ->set('date', '2026-10-09')
            ->call('create')
            ->assertSet('draftMode', true)
            ->set('aircraft_registration', 'pk-lqa')->set('form_station', 'CGK')->set('sta_time', '06:00')->set('std_time', '14:30')
            ->call('save')->assertHasNoErrors();

        $line = LgtRecord::firstOrFail();
        $this->assertSame('PK-LQA', $line->aircraft_registration);
        $this->assertNull($line->cbm_action);
        $this->assertSame($admin->id, $line->drafted_by);
        $this->assertNull($line->filled_at);

        $this->assertSame(1, $finishing->notifications()->count());
        $this->assertStringContainsString('8j 30m', $finishing->notifications()->first()->data['message']);
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_the_draft_form_cannot_write_jobs_or_statuses(): void
    {
        $admin = $this->user(RoleHelper::ADMIN_COD);

        Livewire::actingAs($admin)->test(Index::class)
            ->call('create')
            ->set('aircraft_registration', 'PK-AAA')->set('form_station', 'CGK')->set('form_date', '2026-10-09')
            ->set('cbm_action', 'Sneaky job')->set('cbm_status', 'CLOSED')
            ->call('save');

        $this->assertNull(LgtRecord::firstOrFail()->cbm_action);

        // and the one-click close is not theirs either
        $line = LgtRecord::firstOrFail();
        $line->update(['cbm_action' => 'Real job', 'cbm_status' => 'OPEN']);
        Livewire::actingAs($admin)->test(Index::class)->call('close_', $line->id, 'cbm')->assertForbidden();
        $this->assertSame('OPEN', $line->fresh()->cbm_status);
    }

    public function test_finishing_fills_the_draft_and_closes_the_job(): void
    {
        $finishing = $this->user(RoleHelper::PIC_FINISHING);
        $line = LgtRecord::create(['work_date' => '2026-10-09', 'station' => 'CGK', 'aircraft_registration' => 'PK-LQA', 'sta_time' => '06:00', 'std_time' => '14:30']);

        Livewire::actingAs($finishing)->test(Index::class)->set('date', '2026-10-09')
            ->call('edit', $line->id)->assertSet('draftMode', false)
            ->set('cbm_action', 'Seat belt replacement')->set('cbm_status', 'OPEN')
            ->call('save')->assertHasNoErrors();

        $line->refresh();
        $this->assertSame('Seat belt replacement', $line->cbm_action);
        $this->assertSame($finishing->id, $line->filled_by);
        $this->assertNotNull($line->filled_at);

        Livewire::actingAs($finishing)->test(Index::class)->call('close_', $line->id, 'cbm');
        $this->assertSame('CLOSED', $line->fresh()->cbm_status);
    }

    public function test_roles_without_plan_or_manage_cannot_touch_lgt(): void
    {
        $painting = $this->user(RoleHelper::PIC_PAINTING);

        Livewire::actingAs($painting)->test(Index::class)->call('create')->assertForbidden();
        $this->assertSame(0, LgtRecord::count());
    }
}
