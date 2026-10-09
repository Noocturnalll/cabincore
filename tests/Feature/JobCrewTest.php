<?php

namespace Tests\Feature;

use App\Models\AircraftCleaning;
use App\Models\Employee;
use App\Models\JobCrew;
use App\Services\Aiec\AiecReportImporter;
use App\Services\Crew\CrewWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class JobCrewTest extends TestCase
{
    use RefreshDatabase;

    public function test_refs_are_split_cleaned_and_unique(): void
    {
        $this->assertSame(['250409', '83117667', 'ABD ROUP'], CrewWriter::refs('250409, 83117667;; 250409 , abd roup'));
        $this->assertSame(['1', '2'], CrewWriter::refs([' 1', '2', '', '1']));
        $this->assertSame([], CrewWriter::refs(null));
    }

    public function test_man_hours_are_shared_equally_and_linked_to_employees(): void
    {
        $budi = Employee::create(['nik' => '250409', 'name' => 'BUDI', 'contract_type' => 'PKWTT']);
        $writer = new CrewWriter;

        $writer->replace('cml_log', 7, '250409, 83117667, 999', 3.0, '2026-10-08', 'CGK');

        $rows = JobCrew::where('jobable_id', 7)->orderBy('employee_ref')->get();
        $this->assertCount(3, $rows);
        $this->assertEquals([1.0, 1.0, 1.0], $rows->pluck('man_hour')->all());
        $this->assertSame($budi->id, $rows->firstWhere('employee_ref', '250409')->employee_id);
        $this->assertNull($rows->firstWhere('employee_ref', '999')->employee_id, 'an ID that is not in the employee list is still kept');

        // replacing the crew of the same job does not pile up
        $writer->replace('cml_log', 7, '250409', 3.0, '2026-10-08', 'CGK');
        $this->assertSame(1, JobCrew::where('jobable_id', 7)->count());
        $this->assertEquals(3.0, JobCrew::where('jobable_id', 7)->value('man_hour'));
    }

    public function test_hours_per_person_adds_up_across_jobs(): void
    {
        $w = new CrewWriter;
        $w->replace('cml_log', 1, 'A, B', 2.0, '2026-10-05', 'CGK');
        $w->replace('wo_log', 2, 'A', 3.0, '2026-10-06', 'CGK');
        $w->replace('wo_log', 3, 'A', 9.0, '2026-11-01', 'CGK');   // outside the period

        $rows = $w->hoursPerPerson('2026-10-01', '2026-10-31')->keyBy('employee_ref');

        $this->assertEquals(4.0, $rows['A']->hours);
        $this->assertSame(2, (int) $rows['A']->jobs);
        $this->assertEquals(1.0, $rows['B']->hours);
    }

    public function test_aiec_import_writes_the_crew_for_every_cleaning_job_and_replaces_it_on_reimport(): void
    {
        $book = new Spreadsheet;
        $ws = $book->getActiveSheet();
        $ws->setTitle('SEP');
        $ws->fromArray(['x'], null, 'A1');
        $ws->fromArray(['DATE', 'OPERATOR', 'A/C TYPE', 'A/C REG', 'A/C Status', 'STA', 'SHIFT', 'TASK TYPE', 'DOC TYPE', 'STATUS', 'NO. DOC', 'ACTION TAKEN / REASON', 'START PERFORM', 'FINISH PERFORM', 'MP 1', 'MP 2', 'MP 3'], null, 'A2');
        $ws->fromArray([46266, null, null, 'PK-AAA', 'RON', 'MDC', 'PAGI', 'UNSCHEDULE', 'N/A', 'CLOSED', 'X1', 'TRANSIT', 0.25, 0.3333333333, 111, 222, null], null, 'A3');   // 2 h x 2 people
        $ws->fromArray([46266, null, null, 'PK-BBB', 'RON', 'SUB', 'MALAM', 'UNSCHEDULE', 'N/A', 'CLOSED', 'X2', 'DCI', 0.5, 0.5416666667, 111, null, null], null, 'A4');       // 1 h x 1 person
        $path = tempnam(sys_get_temp_dir(), 'cr').'.xlsx';
        (new Xlsx($book))->save($path);

        $importer = new AiecReportImporter;
        $importer->import($path);
        $importer->import($path);

        $this->assertSame(2, AircraftCleaning::count());
        $this->assertSame(3, JobCrew::where('jobable_type', 'aircraft_cleaning')->count(), 're-import replaced the crew instead of doubling it');

        $per = (new CrewWriter)->hoursPerPerson('2026-09-01', '2026-09-30')->keyBy('employee_ref');
        $this->assertEquals(2.0 + 1.0, $per['111']->hours);   // 2h job shared by two = 2 h each... 4h/2 = 2 ; plus 1 h job
        $this->assertEquals(2.0, $per['222']->hours);
    }
}
