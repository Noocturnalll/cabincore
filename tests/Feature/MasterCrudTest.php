<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Master\Aircraft as AircraftPage;
use App\Livewire\Master\Airports;
use App\Livewire\Master\CapacityConfig;
use App\Livewire\Master\Categories;
use App\Livewire\Master\Divisions;
use App\Livewire\Master\Positions;
use App\Livewire\Master\RonConfig;
use App\Livewire\Modules\AircraftCleaning\Exterior;
use App\Livewire\Modules\IctFinding\Index as IctIndex;
use App\Models\Aircraft;
use App\Models\AircraftCleaning;
use App\Models\Airport;
use App\Models\Aoc;
use App\Models\CapacityStation;
use App\Models\Division;
use App\Models\IctFinding;
use App\Models\JobCategory;
use App\Models\User;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $u->assignRole(RoleHelper::SUPER_ADMIN);
        $this->actingAs($u);
    }

    public function test_airports_create_edit_delete(): void
    {
        Livewire::test(Airports::class)->call('create')
            ->set('kode', 'xyz')->set('nama', 'Test Airport')->set('kota', 'Test City')->set('status', 'Aktif')
            ->call('store')->assertHasNoErrors();
        $a = Airport::where('kode', 'XYZ')->firstOrFail();

        Livewire::test(Airports::class)->call('edit', $a->id)->set('nama', 'Renamed')->call('update')->assertHasNoErrors();
        $this->assertSame('Renamed', $a->fresh()->nama);

        Livewire::test(Airports::class)->call('delete', $a->id);
        $this->assertNull(Airport::find($a->id));
    }

    public function test_airport_validation_rejects_an_empty_form(): void
    {
        Livewire::test(Airports::class)->call('create')->call('store')->assertHasErrors(['kode', 'nama']);
    }

    public function test_aircraft_stores_wg_airline_and_fleet(): void
    {
        $lion = Aoc::create(['code' => 'JT', 'name' => 'Lion Air', 'is_active' => true, 'sort_order' => 1]);

        Livewire::test(AircraftPage::class)->call('create')
            ->set('registration', 'pk-abc')->set('tipe', 'B737-800')->set('maskapai', 'Lion Air')->set('wg', 'wg 05')->set('status', 'Aktif')
            ->call('store')->assertHasNoErrors();

        $ac = Aircraft::where('registration', 'PK-ABC')->firstOrFail();
        $this->assertSame('WG 05', $ac->wg);
        $this->assertSame($lion->id, $ac->aoc_id);
        $this->assertNotNull($ac->fleet);

        Livewire::test(AircraftPage::class)->call('edit', $ac->id)->set('wg', '')->call('update')->assertHasNoErrors();
        $this->assertNull($ac->fresh()->wg);

        Livewire::test(AircraftPage::class)->call('delete', $ac->id);
        $this->assertNull(Aircraft::find($ac->id));
    }

    public function test_aircraft_rejects_an_airline_that_is_not_in_the_aoc_master(): void
    {
        Livewire::test(AircraftPage::class)->call('create')
            ->set('registration', 'PK-ZZZ')->set('tipe', 'B737-800')->set('maskapai', 'Nowhere Air')->set('status', 'Aktif')
            ->call('store')->assertHasErrors(['maskapai']);
    }

    public function test_categories_accept_every_active_division_including_finishing(): void
    {
        Livewire::test(Categories::class)->call('create')
            ->set('kode', 'fin-1')->set('nama', 'Finishing job')->set('divisi', 'Finishing')->set('aktif', 1)
            ->call('store')->assertHasNoErrors();
        $c = JobCategory::where('kode', 'FIN-1')->firstOrFail();

        Livewire::test(Categories::class)->call('edit', $c->id)->set('divisi', 'AIEC')->call('update')->assertHasNoErrors();
        $this->assertSame('AIEC', $c->fresh()->divisi);

        Livewire::test(Categories::class)->call('create')
            ->set('kode', 'bad')->set('nama', 'x')->set('divisi', 'Atlantis')->set('aktif', 1)
            ->call('store')->assertHasErrors(['divisi']);

        Livewire::test(Categories::class)->call('delete', $c->id);
        $this->assertNull(JobCategory::find($c->id));
    }

    public function test_divisions_and_positions_crud(): void
    {
        Livewire::test(Divisions::class)->call('create')->set('name', 'Zeta')->set('status', 'Aktif')->call('store')->assertHasNoErrors();
        $d = Division::where('name', 'Zeta')->firstOrFail();
        Livewire::test(Divisions::class)->call('create')->set('name', 'Zeta')->set('status', 'Aktif')->call('store')->assertHasErrors(['name']);
        Livewire::test(Divisions::class)->call('edit', $d->id)->set('name', 'Zeta2')->call('update')->assertHasNoErrors();
        $this->assertSame('Zeta2', $d->fresh()->name);
        Livewire::test(Divisions::class)->call('delete', $d->id);

        Livewire::test(Positions::class)->call('create')->set('name', 'Tester')->set('status', 'Aktif')->call('store')->assertHasNoErrors();
    }

    public function test_capacity_station_and_ron_crud(): void
    {
        Livewire::test(CapacityConfig::class)->call('createStation')
            ->set('kh_region', 'KH-1')->set('station_code', 'ZZZ')->set('tech_day', 3)->set('tech_night', 2)
            ->call('saveStation')->assertHasNoErrors();
        $s = CapacityStation::where('station_code', 'ZZZ')->firstOrFail();

        Livewire::test(CapacityConfig::class)->call('createStation')
            ->set('kh_region', 'KH-1')->set('station_code', 'ZZZ')->call('saveStation')->assertHasErrors(['station_code']);

        Livewire::test(RonConfig::class)->call('editRon', $s->id)->set('ron_jt', 4)->set('ron_iu', 2)->call('saveRon')->assertHasNoErrors();
        $this->assertSame(4, (int) $s->fresh()->ron_jt);

        Livewire::test(CapacityConfig::class)->call('deleteStation', $s->id);
        $this->assertNull(CapacityStation::find($s->id));
    }

    public function test_ict_finding_can_be_edited(): void
    {
        $f = IctFinding::create(['no_finding' => 'F-1', 'aircraft_registration' => 'PK-A', 'status' => 'Open', 'date' => '2026-10-09']);

        Livewire::test(IctIndex::class)->call('editFinding', $f->id)
            ->set('editStatus', 'Closed')->set('editRemarks', 'done')->call('saveFinding')->assertHasNoErrors();
        $this->assertSame('Closed', $f->fresh()->status);

        Livewire::test(IctIndex::class)->call('editFinding', $f->id)->set('editStatus', 'Bogus')->call('saveFinding')->assertHasErrors(['editStatus']);
    }

    public function test_cleaning_log_create_edit_delete(): void
    {
        Airport::create(['kode' => 'CGK', 'nama' => 'Soekarno-Hatta', 'kota' => 'Jakarta', 'status' => 'Aktif']);

        Livewire::test(Exterior::class)->call('create')
            ->set('aircraft_registration', 'pk-lqa')->set('station', 'CGK')->set('date', '2026-10-09')->set('shift', 'Morning')->set('status', 'Open')
            ->call('save')->assertHasNoErrors();
        $c = AircraftCleaning::where('aircraft_registration', 'PK-LQA')->firstOrFail();

        // the same aircraft + date + type is a duplicate
        Livewire::test(Exterior::class)->call('create')
            ->set('aircraft_registration', 'PK-LQA')->set('station', 'CGK')->set('date', '2026-10-09')->set('shift', 'Morning')->set('status', 'Open')
            ->call('save')->assertHasErrors(['aircraft_registration']);

        Livewire::test(Exterior::class)->call('edit', $c->id)->set('status', 'Closed')->set('start_time', '08:00')->set('end_time', '09:30')->call('save')->assertHasNoErrors();
        $this->assertSame('Closed', $c->fresh()->status);

        Livewire::test(Exterior::class)->call('deleteConfirm', $c->id)->call('delete');
        $this->assertNull(AircraftCleaning::find($c->id));
    }
}
