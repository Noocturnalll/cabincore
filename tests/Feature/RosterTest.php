<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Reports\Manpower;
use App\Models\RosterEntry;
use App\Models\User;
use App\Services\Kpi\ManHourService;
use App\Services\Kpi\ReportPeriod;
use App\Services\Roster\RosterImporter;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class RosterTest extends TestCase
{
    use RefreshDatabase;

    /** Builds a roster workbook: a Code sheet and one station sheet laid out like the real file. */
    private function workbook(): string
    {
        $book = new Spreadsheet;
        $code = $book->getActiveSheet();
        $code->setTitle('Code');
        foreach ([['PAGI', '', 'SIANG', '', 'MALAM'], ['P40', '08:00-17:00', 'S10', '12:00-21:00', 'M11', '19:00-07:00'], ['P33', '07:00-19:00', null, null, 'M21', '20:00-08:00'], ['P00', 'TRAINING']] as $r => $row) {
            foreach ($row as $c => $v) {
                $code->setCellValue([$c + 1, $r + 1], $v);
            }
        }
        // P00 sits in the "other" block
        $code->setCellValue('A4', null)->setCellValue('B4', null)->setCellValue('G2', 'P00')->setCellValue('H2', 'TRAINING')->setCellValue('G3', 'OFF');

        $sta = $book->createSheet();
        $sta->setTitle('CGK');
        $sta->fromArray(['NO', 'EMPLOYEE', 'ID NO', 'POSITION', 46296, 46297, 46298], null, 'A2');   // dates on row 2 (1-Oct .. 3-Oct 2026)
        $sta->fromArray(['', '', '', '', 'Thu', 'Fri', 'Sat', 'DIV'], null, 'A3');
        $sta->fromArray([1, 'BUDI', 83064072, 'MEKANIK', 'P40', 'M11', 'OFF', 'CBM'], null, 'A4');
        $sta->fromArray([2, 'SARI', 83064073, 'STAFF AIEC', 'P33', 'P33', 'M21', 'AIEC'], null, 'A5');
        $sta->fromArray([3, 'ANDI', 83064074, 'PI', 'P40', null, null, 'PI'], null, 'A6');
        $sta->fromArray([4, 'SARI DOBEL', 83064073, 'STAFF AIEC', 'M11', 'M11', 'M11', 'AIEC'], null, 'A7');   // same person twice

        $summary = $book->createSheet();
        $summary->setTitle('MP DAILY');
        $summary->setCellValue('A1', 'not a station');

        $path = tempnam(sys_get_temp_dir(), 'ro').'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    public function test_import_reads_codes_dates_teams_and_ignores_summary_sheets(): void
    {
        $stats = (new RosterImporter)->import($this->workbook());

        $this->assertSame(1, $stats['stations']);
        $this->assertSame(3, $stats['duplicates'], 'a person listed twice is skipped on each of the 3 days');
        $this->assertSame('2026-10-01', $stats['from']);
        $this->assertSame('2026-10-03', $stats['to']);

        $this->assertSame('PAGI', RosterEntry::where('employee_id', '83064072')->whereDate('work_date', '2026-10-01')->value('shift'));
        $this->assertSame('MALAM', RosterEntry::where('employee_id', '83064072')->whereDate('work_date', '2026-10-02')->value('shift'));
        $this->assertNull(RosterEntry::where('employee_id', '83064072')->whereDate('work_date', '2026-10-03')->value('shift'), 'OFF is not a working shift');
        $this->assertSame('AIEC', RosterEntry::where('employee_id', '83064073')->value('team'));
        $this->assertSame('P33', RosterEntry::where('employee_id', '83064073')->whereDate('work_date', '2026-10-01')->value('shift_code'), 'the first row of a duplicated person wins');

        $this->assertSame(7, RosterEntry::count());   // BUDI 3 + SARI 3 + ANDI 1 (empty cells are no entry)

        (new RosterImporter)->import($this->workbook());
        $this->assertSame(7, RosterEntry::count(), 'a re-import replaces the dates instead of adding to them');
    }

    public function test_capacity_comes_from_the_roster_with_per_shift_hours(): void
    {
        (new RosterImporter)->import($this->workbook());

        // 1-3 Oct, teams CBM / AIEC count; PI does not.
        // BUDI: PAGI(8) + MALAM(10) ; SARI: PAGI(8) + PAGI(8) + MALAM(10)
        $capacity = (new ManHourService)->capacity(ReportPeriod::day(Carbon::parse('2026-10-01')));
        $this->assertSame('roster', $capacity['source']);
        $this->assertEquals(8 + 8, $capacity['hours']);                       // 1 Oct: BUDI P40 + SARI P33, both PAGI (8 h each)

        $range = new ReportPeriod(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-03'), 'week');
        $this->assertEquals(8 + 10 + 8 + 8 + 10, (new ManHourService)->capacity($range)['hours']);

        // no roster for the period -> Master Capacity is used
        $this->assertSame('master', (new ManHourService)->capacity(ReportPeriod::day(Carbon::parse('2026-12-01')))['source']);
    }

    public function test_manpower_page_counts_working_people_per_team_and_shift(): void
    {
        (new RosterImporter)->import($this->workbook());
        $this->seed(RegistryPermissionSeeder::class);
        $manager = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $manager->assignRole(RoleHelper::MANAGER);

        Livewire::actingAs($manager)->test(Manpower::class)->set('date', '2026-10-01')->assertSee('CGK');
        $this->actingAs($manager)->get('/reports/manpower')->assertOk();

        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->actingAs($nobody)->get('/reports/manpower')->assertForbidden();
    }
}
