<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Master\Entries;
use App\Models\Division;
use App\Models\MasterEntry;
use App\Models\User;
use App\Services\Kpi\ManHourService;
use App\Services\Kpi\ReportPeriod;
use App\Services\Master\MasterSettings;
use Carbon\Carbon;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);
        MasterSettings::flush();
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $u->assignRole($role);

        return $u;
    }

    public function test_settings_fall_back_to_config_until_the_master_is_filled(): void
    {
        $m = new MasterSettings;

        $this->assertSame(['PAGI' => 8, 'SIANG' => 8, 'MALAM' => 10], $m->shiftHours());
        $this->assertContains('CBM', $m->capacityTeams());
        $this->assertSame(10.0, $m->rule('LATE_TOLERANCE_MIN', 10));
        $this->assertSame('sakit', $m->attendanceGroups()['S']);
        $this->assertSame(['BAIK'], $m->lendableConditions());
    }

    public function test_seeder_fills_defaults_and_never_overwrites_a_changed_value(): void
    {
        $this->seed(MasterDataSeeder::class);
        MasterEntry::where('type', 'shift')->where('code', 'MALAM')->update(['attrs' => ['effective_hours' => 9]]);
        MasterSettings::flush();

        $this->seed(MasterDataSeeder::class);   // a second deploy

        $this->assertEquals(9, MasterEntry::where('code', 'MALAM')->first()->attrs['effective_hours']);
        $this->assertSame(1, MasterEntry::where('type', 'shift')->where('code', 'MALAM')->count());
        $this->assertSame(9.0, (new MasterSettings)->shiftHours()['MALAM']);
    }

    public function test_changing_master_data_changes_the_capacity_calculation(): void
    {
        $this->seed(MasterDataSeeder::class);
        foreach ([['CBM', 'PAGI'], ['CBM', 'MALAM'], ['COD', 'PAGI']] as $i => [$team, $shift]) {
            DB::table('roster_entries')->insert(['work_date' => '2026-10-07', 'station' => 'CGK', 'employee_id' => "E$i", 'team' => $team, 'shift' => $shift, 'shift_code' => 'X', 'created_at' => now(), 'updated_at' => now()]);
        }
        $period = ReportPeriod::day(Carbon::parse('2026-10-07'));

        $this->assertEquals(8 + 10, (new ManHourService)->capacity($period)['hours'], 'COD is not a capacity team');

        MasterEntry::where('type', 'shift')->where('code', 'PAGI')->update(['attrs' => ['effective_hours' => 6]]);
        MasterEntry::where('type', 'team')->where('code', 'COD')->update(['attrs' => ['capacity' => 'Ya']]);
        MasterSettings::flush();

        $this->assertEquals(6 + 10 + 6, (new ManHourService)->capacity($period)['hours']);
    }

    public function test_team_for_division_follows_the_master(): void
    {
        $this->seed(MasterDataSeeder::class);
        $cabin = Division::where('name', 'Cabin')->value('id');

        $this->assertSame('CBM', (new MasterSettings)->teamForDivision($cabin));
        $this->assertNull((new MasterSettings)->teamForDivision(Division::where('name', 'Supporting')->value('id')));
    }

    public function test_each_role_sees_and_edits_only_what_its_permissions_allow(): void
    {
        $this->seed(MasterDataSeeder::class);
        $manager = $this->user(RoleHelper::MANAGER);
        $pic = $this->user(RoleHelper::PIC_CABIN);
        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);

        $this->actingAs($nobody)->get('/master/data/shift')->assertForbidden();
        $this->actingAs($pic)->get('/master/data/shift')->assertOk();
        $this->actingAs($manager)->get('/master/data/nonsense')->assertNotFound();

        // the PIC reads but cannot change
        Livewire::actingAs($pic)->test(Entries::class, ['type' => 'shift'])->call('create')->assertForbidden();

        // the manager can add, edit and switch a value off
        $c = Livewire::actingAs($manager)->test(Entries::class, ['type' => 'shift'])
            ->call('create')->set('code', 'split')->set('label', 'Split')->set('attrs.effective_hours', 7.5)->call('save')->assertHasNoErrors();
        $entry = MasterEntry::where('type', 'shift')->where('code', 'SPLIT')->first();
        $this->assertEquals(7.5, $entry->attrs['effective_hours']);
        $this->assertSame(7.5, (new MasterSettings)->shiftHours()['SPLIT']);

        $c->call('toggle', $entry->id);
        $this->assertArrayNotHasKey('SPLIT', (new MasterSettings)->shiftHours(), 'an inactive entry no longer counts');
    }

    public function test_validation_and_unique_codes_per_type(): void
    {
        $this->seed(MasterDataSeeder::class);
        $manager = $this->user(RoleHelper::MANAGER);

        Livewire::actingAs($manager)->test(Entries::class, ['type' => 'shift'])
            ->call('create')->call('save')->assertHasErrors(['code', 'label', 'attrs.effective_hours'])
            ->set('code', 'PAGI')->set('label', 'Dobel')->set('attrs.effective_hours', 8)->call('save')->assertHasErrors('code');

        // the same code is fine in another type
        Livewire::actingAs($manager)->test(Entries::class, ['type' => 'team'])
            ->call('create')->set('code', 'PAGI')->set('label', 'Tim pagi')->set('attrs.capacity', 'Ya')->call('save')->assertHasNoErrors();
    }
}
