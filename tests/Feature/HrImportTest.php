<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Employee;
use App\Models\RegistryRecord;
use App\Services\Hr\HrExcelImporter;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class HrImportTest extends TestCase
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
        $path = tempnam(sys_get_temp_dir(), 'hr').'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);   // creates the divisions
    }

    public function test_pass_codes_are_normalised(): void
    {
        $i = new HrExcelImporter;
        $this->assertSame('AD, P', $i->pasCodes('Ad, P'));
        $this->assertSame('AD, P', $i->pasCodes('AdP'));
        $this->assertSame('AD, P', $i->pasCodes('ADP'));
        $this->assertSame('A, P', $i->pasCodes('AP'));
        $this->assertSame('PU', $i->pasCodes('P U'));
        $this->assertSame('AD, BD, P', $i->pasCodes('Ad, Bd, P'));
        $this->assertSame('', $i->pasCodes(null));
        $this->assertSame('XYZ', $i->pasCodes('xyz'), 'unknown letters are kept as typed');
    }

    public function test_job_titles_map_to_divisions(): void
    {
        $i = new HrExcelImporter;
        $id = fn ($name) => Division::where('name', $name)->value('id');

        $this->assertSame($id('AIEC'), $i->divisionFor('STAFF AC ( AIRCRAFT CLEANING )'));
        $this->assertSame($id('AIEC'), $i->divisionFor('GROUP LEADER AIEC HLP'));
        $this->assertSame($id('AIEC'), $i->divisionFor('GROUP LEADER AIC T1A-B'));
        $this->assertSame($id('Cabin'), $i->divisionFor('MEKANIK CBM'));
        $this->assertSame($id('Cabin'), $i->divisionFor('SURVEYOR ( PI )'));
        $this->assertSame($id('Painting'), $i->divisionFor('PIC AIRCRAFT PAINTING'));
        $this->assertSame($id('Team Irreg'), $i->divisionFor('GROUP LEADER CBM IRREG & PROJECT A'), 'IRREG wins over CBM');
        $this->assertSame($id('Supporting'), $i->divisionFor('STAFF CABIN SUPPORTING'), 'SUPPORTING wins over CABIN');
        $this->assertNull($i->divisionFor('DRIVER'));
    }

    public function test_employee_import_reads_the_database_sheet_and_is_rerunnable(): void
    {
        $path = $this->xlsx('DATABASE', [
            ['Judul'],
            ['NO', 'NAMA', 'NO. ID', 'STATION', 'JABATAN', 'DIREKTORAT', 'JENIS KELAMIN', 'ATASAN LANGSUNG ( PIC, GL )', 'TMT'],
            [1, 'BUDI', 83064072, 'cgk', 'MEKANIK CBM', 'BAT', 'L', 'ANDI', 45000],
            [2, 'SARI', 'TL130369', 'DMK', 'DRIVER', 'AAS', 'P', 'BUDI', null],
        ]);

        $importer = new HrExcelImporter;
        $dry = $importer->importEmployees($path, true);
        $this->assertSame(2, $dry['read']);
        $this->assertSame(0, Employee::count(), 'dry run changes nothing');
        $this->assertSame(['DRIVER' => 1], $dry['no_division']);

        $importer->importEmployees($path);
        $second = $importer->importEmployees($path);
        $this->assertSame(['created' => 0, 'updated' => 2], ['created' => $second['created'], 'updated' => $second['updated']]);
        $this->assertSame(2, Employee::count());

        $budi = Employee::where('nik', '83064072')->first();
        $this->assertSame('CGK', $budi->station);
        $this->assertSame('MEKANIK CBM', $budi->job_title);
        $this->assertSame(Division::where('name', 'Cabin')->value('id'), $budi->division_id);
        $this->assertSame('ANDI', $budi->supervisor_name);
        $this->assertSame('2023-03-15', $budi->join_date->toDateString());
    }

    public function test_pasban_import_keeps_the_latest_answer_and_updates_passports(): void
    {
        Employee::create(['nik' => '83064072', 'name' => 'BUDI', 'contract_type' => 'PKWTT', 'division_id' => Division::where('name', 'Cabin')->value('id')]);

        $path = $this->xlsx('Form Responses 1', [
            ['Timestamp', 'Nama', 'ID', 'STA', 'Email Aktif', 'Kode Pas Bandara', 'Tanggal Exp Pas Bandara', 'Nomor Passport (Jika ada)', 'Tanggal Exp Passport', 'Status Pasban'],
            [46267.5, 'BUDI', 83064072, 'CGK', 'a@x', 'P', 46379, null, null, null],
            [46268.5, 'BUDI', 83064072, 'CGK', 'a@x', 'Ad,P', 46500, 'A1234567', 47000, 'SCHEDULED FOR 2027'],   // newer
            [46269.5, 'TAMU', 999, 'SUB', 'b@x', 'PU', 46600, null, null, null],                                 // not in the employee list
        ]);

        $stats = (new HrExcelImporter)->importPasban($path);

        $this->assertSame(['read' => 3, 'people' => 2, 'pas' => 2, 'passports' => 1, 'unknown_employees' => 1], $stats);

        $pas = RegistryRecord::where('module', 'pas')->where('data->nik', '83064072')->first();
        $this->assertSame('AD, P', $pas->data['codes'], 'the newer answer wins');
        $this->assertSame('2027-04-23', $pas->due_date->toDateString());   // 46500
        $this->assertSame('SCHEDULED FOR 2027', $pas->data['notes']);
        $this->assertSame(Division::where('name', 'Cabin')->value('id'), $pas->division_id, 'division follows the employee');

        $this->assertSame('A1234567', Employee::where('nik', '83064072')->value('passport_no'));

        (new HrExcelImporter)->importPasban($path);
        $this->assertSame(2, RegistryRecord::where('module', 'pas')->count(), 're-import does not duplicate');
    }
}
