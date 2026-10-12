<?php

namespace Tests\Feature;

use App\Exports\AttendanceExport;
use App\Exports\EmployeeExport;
use App\Exports\ImsReportExport;
use App\Exports\LgtExport;
use App\Exports\NsrdiOverdueExport;
use App\Helpers\RoleHelper;
use App\Livewire\Modules\Attendance\Index as AttendanceIndex;
use App\Livewire\Modules\Hr\Employees as HrEmployees;
use App\Livewire\Modules\Ims\Report\Index as ImsReportIndex;
use App\Livewire\Modules\Lgt\Index as LgtIndex;
use App\Livewire\Modules\NsrdiOverdue\Index as NsrdiOverdueIndex;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Ims\Category;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\StockMovement;
use App\Models\Ims\Unit;
use App\Models\LgtRecord;
use App\Models\NsrdiOverdue;
use App\Models\User;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MandatoryExportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create([
            'is_default_password' => false,
            'status' => 'active',
            'station' => 'CGK',
        ]);
        $user->assignRole(RoleHelper::SUPER_ADMIN);

        return $user;
    }

    public function test_attendance_export_includes_pivot_sheets(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $from = now()->subDays(7);
        $to = now();

        $export = new AttendanceExport($from, $to, ['CGK'], null, 'overview');
        $sheets = $export->sheets();

        $this->assertGreaterThanOrEqual(2, count($sheets));
        $this->assertSame('Pivot Rekapitulasi', $sheets[0]->title());
        $this->assertSame('Detail Presensi Karyawan', $sheets[1]->title());

        Livewire::test(AttendanceIndex::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_employee_export_includes_pivot_sheets(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $div = Division::create(['name' => 'Cabin Maintenance', 'status' => 'Aktif']);
        Employee::create([
            'nik' => '99001',
            'name' => 'Budi Santoso',
            'division_id' => $div->id,
            'station' => 'CGK',
            'status' => 'Aktif',
            'contract_type' => 'PKWT',
            'contract_start' => now()->subMonths(6),
            'contract_end' => now()->addMonths(2),
        ]);

        $export = new EmployeeExport;
        $sheets = $export->sheets();

        $this->assertCount(2, $sheets);
        $this->assertSame('Pivot Rekapitulasi', $sheets[0]->title());
        $this->assertSame('Data Karyawan', $sheets[1]->title());

        Livewire::test(HrEmployees::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_nsrdi_overdue_export_includes_pivot_sheets(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        NsrdiOverdue::create([
            'operator' => 'LION AIR',
            'aircraft_registration' => 'PK-LHF',
            'ac_status' => 'Normal',
            'nsrdi_number' => 'NSR-2026-001',
            'status' => 'Open',
            'month_due' => 'Oct 2026',
            'description' => 'Cabin seat wear',
        ]);

        $export = new NsrdiOverdueExport;
        $sheets = $export->sheets();

        $this->assertCount(2, $sheets);
        $this->assertSame('Pivot Rekapitulasi', $sheets[0]->title());
        $this->assertSame('Detail NSRDI Overdue', $sheets[1]->title());

        Livewire::test(NsrdiOverdueIndex::class)
            ->call('exportData')
            ->assertFileDownloaded();
    }

    public function test_ims_report_export_includes_pivot_sheets(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $cat = Category::create(['name' => 'Seat Parts', 'code' => 'SP', 'is_active' => true]);
        $loc = Location::create(['name' => 'Rak A1', 'code' => 'RA1', 'type' => 'rack', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Piece', 'code' => 'PCS', 'is_active' => true]);
        $item = Item::create([
            'name' => 'Seat Cushion',
            'part_number' => 'SC-01',
            'description' => 'Cushion busa kursi kabin',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        StockMovement::create([
            'item_id' => $item->id,
            'location_id' => $loc->id,
            'movement_type' => 'in',
            'balance_before' => 10,
            'balance_after' => 15,
            'qty_change' => 5,
        ]);

        $export = new ImsReportExport;
        $sheets = $export->sheets();

        $this->assertCount(2, $sheets);
        $this->assertSame('Pivot Rekapitulasi', $sheets[0]->title());
        $this->assertSame('Detail Mutasi Stok', $sheets[1]->title());

        Livewire::test(ImsReportIndex::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_lgt_export_includes_pivot_sheets(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        LgtRecord::create([
            'work_date' => now()->toDateString(),
            'station' => 'CGK',
            'aircraft_registration' => 'PK-LHF',
            'aoc' => 'JT',
            'sta_time' => '08:00',
            'std_time' => '14:00',
            'cbm_action' => 'Deep clean & inspect',
            'cbm_status' => 'OPEN',
            'aiec_status' => 'OPEN',
        ]);

        $export = new LgtExport(now()->toDateString());
        $sheets = $export->sheets();

        $this->assertCount(2, $sheets);
        $this->assertSame('Pivot Rekapitulasi', $sheets[0]->title());
        $this->assertSame('Detail Jadwal LGT', $sheets[1]->title());

        Livewire::test(LgtIndex::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }
}
