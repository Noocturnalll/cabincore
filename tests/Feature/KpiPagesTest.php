<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Reports\DocumentAccuracy;
use App\Livewire\Reports\Lgt;
use App\Models\DocumentAccuracy as Accuracy;
use App\Models\LgtRecord;
use App\Models\User;
use App\Services\Kpi\KpiExcelImporter;
use App\Services\Kpi\ReportPeriod;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class KpiPagesTest extends TestCase
{
    use RefreshDatabase;

    private function xlsx(string $sheet, array $rows): string
    {
        $book = new Spreadsheet;
        $ws = $book->getActiveSheet();
        $ws->setTitle($sheet);
        foreach ($rows as $r => $row) {
            foreach (array_values($row) as $c => $v) {
                $ws->setCellValue([$c + 1, $r + 1], $v);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'kpi').'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    private function manager(): User
    {
        $this->seed(RegistryPermissionSeeder::class);
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $u->assignRole(RoleHelper::MANAGER);

        return $u;
    }

    public function test_lgt_week_can_start_on_monday(): void
    {
        // 2026-10-08 is a Thursday: Mon-Sun week = 5-11 Oct, default Thu-Wed week = 8-14 Oct
        $this->assertSame('2026-10-05', ReportPeriod::week(Carbon::parse('2026-10-08'), Carbon::MONDAY)->from->toDateString());
        $this->assertSame('2026-10-11', ReportPeriod::week(Carbon::parse('2026-10-08'), Carbon::MONDAY)->to->toDateString());
        $this->assertSame('2026-10-08', ReportPeriod::week(Carbon::parse('2026-10-08'))->from->toDateString());
    }

    public function test_document_accuracy_import_adds_up_duplicate_days_and_skips_empty_future_rows(): void
    {
        $path = $this->xlsx('DATA', [
            ['', '', 'REPORTED', '', 'RECORDED', '', 'MISSED'],
            ['DATE', 'STA', 'CML', 'NSRDI', 'CML', 'NSRDI', 'CML', 'NSRDI', 'TOTAL', 'TOTAL', 'TOTAL', '% ACC', 'REMARKS'],
            [46266, 'CGK', 89, 22, null, null, 6, 2, null, null, null, null, 'OK'],
            [46266, 'CGK', 11, 3, null, null, 1, 0, null, null, null, null, 'TRAINING'],   // same day listed twice
            [46266, 'HLP', 18, 0, null, null, 0, 0, null, null, null, null, null],
            [46267, 'HLP', null, null, null, null, null, null, null, null, null, null, null], // pre-filled day
        ]);

        $stats = (new KpiExcelImporter)->importDocumentAccuracy($path);

        $this->assertSame(['read' => 3, 'saved' => 2], ['read' => $stats['read'], 'saved' => $stats['saved']]);
        $cgk = Accuracy::where('station', 'CGK')->first();
        $this->assertSame(100, $cgk->cml_reported);
        $this->assertSame(25, $cgk->nsrdi_reported + 0 === 25 ? 25 : 0);
        $this->assertSame(7, $cgk->cml_missed);
        $this->assertSame('OK; TRAINING', $cgk->remarks);

        (new KpiExcelImporter)->importDocumentAccuracy($path);   // re-run does not duplicate
        $this->assertSame(2, Accuracy::count());
    }

    public function test_lgt_import_replaces_the_date_range_and_keeps_several_tasks_per_aircraft(): void
    {
        $header = ['DATE', '', 'AC REG', 'AOC', 'STA', 'STD', 'GROUND TIME', 'ACTION TAKEN / REASON', 'MP INCH', 'STATUS', 'ACTION TAKEN (AIEC)', 'MP INCH', 'STATUS', 'REASON (IF OPEN)'];
        $rows = [
            ['LGT'],
            $header,
            [46313, 'CGK', 'PK-LBW', 'BATIK AIR', 0.5, 0.6, null, 'TASK ONE', 'ANIS', 'CLOSED', 'CLEANING', 'SITI', 'CLOSED', null],
            [46313, 'CGK', 'PK-LBW', 'BATIK AIR', 0.5, 0.6, null, 'TASK TWO', 'ANIS', 'CLOSED', 'CLEANING', 'SITI', 'CLOSED', null],
            [46313, 'UPG', 'PK-LDK', 'BATIK AIR', 0.3, 0.4, null, null, 'RIZAL', 'CANCEL', null, 'DINDA', 'CANCEL', 'AC ROTATION CHANGED'],
        ];
        $path = $this->xlsx('DATA', $rows);

        $importer = new KpiExcelImporter;
        $first = $importer->importLgt($path);
        $this->assertSame(3, $first['saved']);
        $this->assertSame(3, LgtRecord::count());

        $importer->importLgt($path);
        $this->assertSame(3, LgtRecord::count(), 'a re-import replaces the same dates instead of adding to them');

        $upg = LgtRecord::where('station', 'UPG')->first();
        $this->assertSame('CANCEL', $upg->cbm_status);
        $this->assertSame('AC ROTATION CHANGED', $upg->reason);
        $this->assertSame('07:12', LgtRecord::where('station', 'UPG')->value('sta_time'));
    }

    public function test_pages_show_achievement_per_station(): void
    {
        $manager = $this->manager();
        Carbon::setTestNow('2026-10-08 10:00:00');

        Accuracy::create(['work_date' => '2026-10-07', 'station' => 'CGK', 'cml_reported' => 90, 'nsrdi_reported' => 10, 'cml_missed' => 5, 'nsrdi_missed' => 0]);
        foreach ([['CLOSED', 'CLOSED'], ['CLOSED', 'CANCEL'], ['CANCEL', 'CANCEL'], ['CLOSED', 'CLOSED']] as [$cbm, $aiec]) {
            LgtRecord::create(['work_date' => '2026-10-07', 'station' => 'UPG', 'aircraft_registration' => 'PK-A', 'cbm_status' => $cbm, 'aiec_status' => $aiec, 'reason' => $cbm === 'CANCEL' ? 'NO MP' : null]);
        }

        Livewire::actingAs($manager)->test(DocumentAccuracy::class)->set('kind', 'day')->set('date', '2026-10-07')
            ->assertSee('CGK')->assertSee('95%');
        Livewire::actingAs($manager)->test(Lgt::class)->set('kind', 'day')->set('date', '2026-10-07')
            ->assertSee('UPG')->assertSee('75%')->assertSee('NO MP');

        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->actingAs($nobody)->get('/reports/lgt')->assertForbidden();
        $this->actingAs($manager)->get('/reports/document-accuracy')->assertOk();

        Carbon::setTestNow();
    }
}
