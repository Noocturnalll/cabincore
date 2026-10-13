<?php

namespace Tests\Feature;

use App\Exports\NsrdiExport;
use App\Helpers\RoleHelper;
use App\Imports\NsrdiImport;
use App\Livewire\Modules\NsrdiLog\Index;
use App\Livewire\Modules\Painting\DailyReport;
use App\Models\DailyJobAssignment;
use App\Models\Division;
use App\Models\NsrdiLog;
use App\Models\User;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NsrdiSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $picPainting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);

        $this->manager = User::factory()->create([
            'is_default_password' => false,
            'status' => 'active',
            'division_id' => Division::where('name', 'Cabin')->value('id'),
        ]);
        $this->manager->assignRole(RoleHelper::MANAGER);

        $this->picPainting = User::factory()->create([
            'is_default_password' => false,
            'status' => 'active',
            'division_id' => Division::where('name', 'Painting')->value('id'),
        ]);
        $this->picPainting->assignRole(RoleHelper::PIC_PAINTING);
    }

    public function test_sidebar_displays_aircraft_painting_and_separated_nsrdi_route(): void
    {
        $response = $this->actingAs($this->picPainting)->get('/modules/painting/nsrdi');
        $response->assertOk();
        $response->assertSee('Aircraft Painting');
        $response->assertSee(route('modules.painting.nsrdi'));
    }

    public function test_cabin_maintenance_nsrdi_shows_only_cbm_records(): void
    {
        $dja = DailyJobAssignment::create([
            'aircraft_registration' => 'PK-GAA',
            'date' => now()->format('Y-m-d'),
            'job_type' => 'AOC/NSRDI',
            'station' => 'CGK',
            'task_id' => 'TASK-CBM',
        ]);

        $cbmLog = NsrdiLog::create([
            'dja_id' => $dja->id,
            'aircraft_registration' => 'PK-GAA',
            'nsrdi_number' => 'NSRDI-CBM-001',
            'description' => 'Tray table latch broken',
            'category' => 'CBM',
            'plan_date' => now()->format('Y-m-d'),
            'report_date' => now()->format('Y-m-d'),
            'status' => 'Open',
        ]);

        $paintLog = NsrdiLog::create([
            'dja_id' => $dja->id,
            'aircraft_registration' => 'PK-GAA',
            'nsrdi_number' => 'NSRDI-PNT-001',
            'description' => 'Perform repaint LH wing slat',
            'category' => 'PAINTING',
            'plan_date' => now()->format('Y-m-d'),
            'report_date' => now()->format('Y-m-d'),
            'status' => 'Open',
        ]);

        Livewire::actingAs($this->manager)
            ->test(Index::class)
            ->set('dateFilter', now()->format('Y-m-d'))
            ->assertSee('NSRDI Cabin Maintenance')
            ->assertSee('NSRDI-CBM-001')
            ->assertDontSee('NSRDI-PNT-001');
    }

    public function test_aircraft_painting_nsrdi_shows_only_painting_records(): void
    {
        $dja = DailyJobAssignment::create([
            'aircraft_registration' => 'PK-GAA',
            'date' => now()->format('Y-m-d'),
            'job_type' => 'AOC/NSRDI',
            'station' => 'CGK',
            'task_id' => 'TASK-PNT',
        ]);

        $cbmLog = NsrdiLog::create([
            'dja_id' => $dja->id,
            'aircraft_registration' => 'PK-GAA',
            'nsrdi_number' => 'NSRDI-CBM-002',
            'description' => 'Armrest cushion broken',
            'category' => 'CBM',
            'plan_date' => now()->format('Y-m-d'),
            'report_date' => now()->format('Y-m-d'),
            'status' => 'Open',
        ]);

        $paintLog = NsrdiLog::create([
            'dja_id' => $dja->id,
            'aircraft_registration' => 'PK-GAA',
            'nsrdi_number' => 'NSRDI-PNT-002',
            'description' => 'Paint peel off fuselage section 41',
            'category' => 'PAINTING',
            'plan_date' => now()->format('Y-m-d'),
            'report_date' => now()->format('Y-m-d'),
            'status' => 'Open',
        ]);

        Livewire::actingAs($this->picPainting)
            ->test(Index::class, ['category' => 'PAINTING'])
            ->set('dateFilter', now()->format('Y-m-d'))
            ->assertSee('NSRDI Aircraft Painting')
            ->assertSee('NSRDI-PNT-002')
            ->assertDontSee('NSRDI-CBM-002');
    }

    public function test_auto_categorization_on_nsrdi_log_creation_and_saving(): void
    {
        // 1. Explicit painting category
        $log1 = NsrdiLog::create([
            'aircraft_registration' => 'PK-GAB',
            'nsrdi_number' => 'TEST-001',
            'description' => 'Some touch up required',
            'category' => 'PAINTING',
        ]);
        $this->assertSame('PAINTING', $log1->category);

        // 2. Keyword 'repaint' in description
        $log2 = NsrdiLog::create([
            'aircraft_registration' => 'PK-GAB',
            'nsrdi_number' => 'TEST-002',
            'description' => 'Perform repaint Electrical panel 121AL area',
            'category' => null,
        ]);
        $this->assertSame('PAINTING', $log2->category);

        // 3. Keyword 'ppo' in description
        $log3 = NsrdiLog::create([
            'aircraft_registration' => 'PK-GAB',
            'nsrdi_number' => 'TEST-003',
            'description' => 'FUSELAGE SECT 41 DUE TO PPO C/O',
            'category' => null,
        ]);
        $this->assertSame('PAINTING', $log3->category);

        // 4. Keyword 'pengecatan' in description
        $log4 = NsrdiLog::create([
            'aircraft_registration' => 'PK-GAB',
            'nsrdi_number' => 'TEST-004',
            'description' => 'Pengecatan area nose cowl mesin',
            'category' => null,
        ]);
        $this->assertSame('PAINTING', $log4->category);

        // 5. Category specified as 'cat painting'
        $log5 = NsrdiLog::create([
            'aircraft_registration' => 'PK-GAB',
            'nsrdi_number' => 'TEST-005',
            'description' => 'Area marking touch up',
            'category' => 'cat painting',
        ]);
        $this->assertSame('PAINTING', $log5->category);

        // 6. Standard cabin log defaults to CBM
        $log6 = NsrdiLog::create([
            'aircraft_registration' => 'PK-GAB',
            'nsrdi_number' => 'TEST-006',
            'description' => 'TROLLEY CATERING CANT LOCKED',
            'category' => null,
        ]);
        $this->assertSame('CBM', $log6->category);
    }

    public function test_nsrdi_import_auto_categorizes_correctly(): void
    {
        $importer = new NsrdiImport;

        // Row with 'cat' column = painting
        $model1 = $importer->model([
            'nsrdi_number' => 'IMP-001',
            'ac_reg' => 'PK-GAC',
            'description' => 'General inspection',
            'cat' => 'PAINTING',
        ]);
        $this->assertSame('PAINTING', $model1->category);

        // Row with repaint description
        $model2 = $importer->model([
            'nsrdi_number' => 'IMP-002',
            'ac_reg' => 'PK-GAC',
            'description' => 'Repaint LH wing slat area',
        ]);
        $this->assertSame('PAINTING', $model2->category);

        // Standard row defaults to CBM
        $model3 = $importer->model([
            'nsrdi_number' => 'IMP-003',
            'ac_reg' => 'PK-GAC',
            'description' => 'Passenger seat buckle jammed',
        ]);
        $this->assertSame('CBM', $model3->category);
    }

    public function test_nsrdi_export_filters_by_category(): void
    {
        NsrdiLog::create([
            'aircraft_registration' => 'PK-GAD',
            'nsrdi_number' => 'EXP-CBM',
            'description' => 'Seat belt replace',
            'category' => 'CBM',
        ]);

        NsrdiLog::create([
            'aircraft_registration' => 'PK-GAD',
            'nsrdi_number' => 'EXP-PNT',
            'description' => 'Perform repaint elevator',
            'category' => 'PAINTING',
        ]);

        $cbmExport = new NsrdiExport('', '', '', 'CBM');
        $cbmData = $cbmExport->collection();
        $this->assertTrue($cbmData->contains('nsrdi_number', 'EXP-CBM'));
        $this->assertFalse($cbmData->contains('nsrdi_number', 'EXP-PNT'));

        $paintExport = new NsrdiExport('', '', '', 'PAINTING');
        $paintData = $paintExport->collection();
        $this->assertTrue($paintData->contains('nsrdi_number', 'EXP-PNT'));
        $this->assertFalse($paintData->contains('nsrdi_number', 'EXP-CBM'));
    }

    public function test_painting_daily_report_shows_only_painting_records(): void
    {
        $dja = DailyJobAssignment::create([
            'aircraft_registration' => 'PK-PNT',
            'date' => '2026-10-01',
            'job_type' => 'AOC/NSRDI',
            'station' => 'CGK',
            'task_id' => 'TASK-DR-PNT',
        ]);

        NsrdiLog::create([
            'dja_id' => $dja->id,
            'aircraft_registration' => 'PK-PNT',
            'nsrdi_number' => 'DR-PNT-001',
            'description' => 'Perform repaint rudder C/O',
            'category' => 'PAINTING',
            'plan_date' => '2026-10-01',
            'status' => 'Closed',
            'is_submitted' => true,
        ]);

        NsrdiLog::create([
            'dja_id' => $dja->id,
            'aircraft_registration' => 'PK-CBM',
            'nsrdi_number' => 'DR-CBM-001',
            'description' => 'Passenger seat table broken',
            'category' => 'CBM',
            'plan_date' => '2026-10-01',
            'status' => 'Closed',
            'is_submitted' => true,
        ]);

        Livewire::actingAs($this->picPainting)
            ->test(DailyReport::class)
            ->set('activeTab', 'dja-painting')
            ->set('dateFilter', '2026-10-01')
            ->assertSee('DR-PNT-001')
            ->assertDontSee('DR-CBM-001');

        $response = $this->actingAs($this->picPainting)->get('/modules/painting/daily-report');
        $response->assertOk();
        $response->assertSee('Daily Report - Aircraft Painting');
    }

    public function test_cabin_daily_report_excludes_painting_records(): void
    {
        $dja = DailyJobAssignment::create([
            'aircraft_registration' => 'PK-MIX',
            'date' => '2026-10-01',
            'job_type' => 'AOC/NSRDI',
            'station' => 'CGK',
            'task_id' => 'TASK-DR-MIX',
        ]);

        NsrdiLog::create([
            'dja_id' => $dja->id,
            'aircraft_registration' => 'PK-MIX',
            'nsrdi_number' => 'DR-CBM-002',
            'description' => 'Lavatory door latch damaged',
            'category' => 'CBM',
            'plan_date' => '2026-10-01',
            'status' => 'Closed',
            'is_submitted' => true,
        ]);

        NsrdiLog::create([
            'dja_id' => $dja->id,
            'aircraft_registration' => 'PK-MIX',
            'nsrdi_number' => 'DR-PNT-002',
            'description' => 'Paint touch up on flap track fairing',
            'category' => 'PAINTING',
            'plan_date' => '2026-10-01',
            'status' => 'Closed',
            'is_submitted' => true,
        ]);

        Livewire::actingAs($this->manager)
            ->test(\App\Livewire\Modules\DailyReport\Index::class)
            ->set('activeTab', 'dja-nsrdi')
            ->set('dateFilter', '2026-10-01')
            ->assertSee('DR-CBM-002')
            ->assertDontSee('DR-PNT-002');
    }
}
