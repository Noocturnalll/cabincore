<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\Attendance\Index;
use App\Models\AttendanceRecord;
use App\Models\MasterEntry;
use App\Models\ShiftCode;
use App\Models\User;
use App\Services\Attendance\AttendanceImporter;
use App\Services\Attendance\AttendanceScorer;
use App\Services\Master\MasterSettings;
use Carbon\Carbon;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        MasterSettings::flush();

        ShiftCode::create(['code' => 'P40', 'shift' => 'PAGI', 'time_range' => '08:00-17:00']);
        ShiftCode::create(['code' => 'M11', 'shift' => 'MALAM', 'time_range' => '19:00-07:00']);
    }

    private function xlsx(array $rows): string
    {
        $book = new Spreadsheet;
        $ws = $book->getActiveSheet();
        foreach ($rows as $r => $row) {
            foreach (array_values($row) as $c => $v) {
                $ws->setCellValue([$c + 1, $r + 1], $v);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'at').'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    private function roster(string $nik, string $name, string $date, string $code, ?string $shift, string $station = 'CGK', string $team = 'CBM'): void
    {
        DB::table('roster_entries')->insert(['work_date' => $date, 'station' => $station, 'employee_id' => $nik, 'employee_name' => $name, 'team' => $team, 'shift_code' => $code, 'shift' => $shift, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function periodFrom(): Carbon
    {
        return Carbon::parse('2026-10-01');
    }

    private function periodTo(): Carbon
    {
        return Carbon::parse('2026-10-31');
    }

    public function test_list_import_works_out_lateness_from_the_roster_shift(): void
    {
        $this->roster('111', 'BUDI', '2026-10-05', 'P40', 'PAGI');
        $this->roster('111', 'BUDI', '2026-10-06', 'P40', 'PAGI');
        $this->roster('111', 'BUDI', '2026-10-07', 'M11', 'MALAM');

        $path = $this->xlsx([
            ['ID', 'Nama', 'Tanggal', 'Status', 'Jam Masuk', 'Jam Pulang'],
            [111, 'BUDI', '2026-10-05', 'Hadir', '08:05', '17:00'],   // 5 min: inside the 10 min tolerance
            [111, 'BUDI', '2026-10-06', 'Hadir', '08:25', '16:30'],   // 25 min late, left 30 min early
            [111, 'BUDI', '2026-10-07', '', '19:45', '07:00'],        // no status but a clock-in -> present, 45 min late
            [111, 'BUDI', '2026-10-08', 'Sakit', '', ''],
            [111, 'BUDI', '2026-10-09', 'Bolos', '', ''],            // unknown word
        ]);

        $stats = (new AttendanceImporter)->import($path);

        $this->assertSame(['read' => 5, 'saved' => 4, 'skipped' => 1, 'late' => 2], ['read' => $stats['read'], 'saved' => $stats['saved'], 'skipped' => $stats['skipped'], 'late' => $stats['late']]);
        $this->assertSame(['BOLOS' => 1], $stats['unknown_status']);

        $day = fn (string $d) => AttendanceRecord::where('employee_nik', '111')->whereDate('work_date', $d)->first();
        $this->assertSame(0, $day('2026-10-05')->late_minutes, 'within tolerance');
        $this->assertSame(25, $day('2026-10-06')->late_minutes);
        $this->assertSame(30, $day('2026-10-06')->early_leave_minutes);
        $this->assertSame('H', $day('2026-10-07')->status_code);
        $this->assertSame(45, $day('2026-10-07')->late_minutes, 'night shift measured against 19:00');
        $this->assertSame('sakit', $day('2026-10-08')->status_group);

        (new AttendanceImporter)->import($path);
        $this->assertSame(4, AttendanceRecord::count(), 're-import replaces the same person/day');
    }

    public function test_matrix_layout_with_status_codes_per_date_is_read_too(): void
    {
        $dates = [];
        for ($i = 0; $i < 8; $i++) {
            $dates[] = 46296 + $i;   // 1-8 Oct 2026
        }
        $path = $this->xlsx([
            array_merge(['NO', 'NAMA', 'ID', 'STA'], $dates),
            array_merge([1, 'SARI', 222, 'HLP'], ['H', 'H', 'S', 'S', 'C', 'OFF', 'H', 'A']),
        ]);

        $stats = (new AttendanceImporter)->import($path);

        $this->assertSame(8, $stats['saved']);
        $groups = AttendanceRecord::where('employee_nik', '222')->orderBy('work_date')->pluck('status_group')->all();
        $this->assertSame(['hadir', 'hadir', 'sakit', 'sakit', 'cuti', 'libur', 'hadir', 'alpa'], $groups);
        $this->assertSame('HLP', AttendanceRecord::first()->station);
    }

    public function test_an_unreadable_file_is_rejected_with_a_clear_message(): void
    {
        $this->expectExceptionMessage('Format presensi tidak dikenali');
        (new AttendanceImporter)->import($this->xlsx([['foo', 'bar'], [1, 2]]));
    }

    public function test_scoring_flags_the_right_people_and_scales_limits_by_month(): void
    {
        $days = ['05', '06', '07', '08', '09', '12', '13', '14', '15', '16'];
        foreach ($days as $d) {
            foreach (['111' => 'RAJIN', '222' => 'TELAT', '333' => 'SAKIT', '444' => 'CUTI'] as $nik => $name) {
                $this->roster($nik, $name, "2026-10-$d", 'P40', 'PAGI');
            }
        }
        // sick / leave come straight from the roster codes
        foreach (['05', '06', '07', '08'] as $d) {
            DB::table('roster_entries')->where('employee_id', '333')->whereDate('work_date', "2026-10-$d")->update(['shift_code' => 'S', 'shift' => null]);
        }
        foreach (['05', '06', '07', '08', '09', '12'] as $d) {
            DB::table('roster_entries')->where('employee_id', '444')->whereDate('work_date', "2026-10-$d")->update(['shift_code' => 'C', 'shift' => null]);
        }
        // imported attendance for 111 and 222 only (so they are not "assumed")
        foreach ($days as $i => $d) {
            foreach (['111', '222'] as $nik) {
                AttendanceRecord::create(['employee_nik' => $nik, 'station' => 'CGK', 'work_date' => "2026-10-$d", 'status_code' => 'H', 'status_group' => 'hadir',
                    'late_minutes' => $nik === '222' && $i < 5 ? 20 : 0]);   // 222 is late 5 times (limit 3)
            }
        }

        $result = (new AttendanceScorer)->build($this->periodFrom(), $this->periodTo());
        $rows = $result['rows']->keyBy('nik');

        $this->assertSame(['rajin'], $rows['111']['flags']);
        $this->assertEquals(100.0, $rows['111']['on_time']);
        $this->assertSame(['sering_terlambat'], $rows['222']['flags']);
        $this->assertSame(5, $rows['222']['late']);
        $this->assertEquals(100, $rows['222']['late_minutes']);
        $this->assertSame(['banyak_sakit'], $rows['333']['flags']);   // 4 sick days > 3
        $this->assertSame(['banyak_cuti'], $rows['444']['flags']);    // 6 leave days > 5
        $this->assertTrue($rows['333']['assumed'], 'no imported attendance: presence is assumed from the roster');

        // the same people over a year: limits are x12, so nobody is flagged for sick or leave any more
        $year = (new AttendanceScorer)->build(Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'))['rows']->keyBy('nik');
        $this->assertSame(12, (new AttendanceScorer)->build(Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'))['months']);
        $this->assertNotContains('banyak_sakit', $year['333']['flags']);
        $this->assertNotContains('banyak_cuti', $year['444']['flags']);
        $this->assertContains('rajin', $year['111']['flags']);
    }

    public function test_changing_a_rule_in_the_master_changes_the_verdict(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $this->roster('555', 'ANDI', sprintf('2026-10-%02d', $i), 'P40', 'PAGI');
            AttendanceRecord::create(['employee_nik' => '555', 'station' => 'CGK', 'work_date' => sprintf('2026-10-%02d', $i), 'status_code' => 'H', 'status_group' => 'hadir', 'late_minutes' => $i <= 2 ? 15 : 0]);
        }
        $rows = fn () => (new AttendanceScorer)->build($this->periodFrom(), $this->periodTo())['rows']->keyBy('nik');

        $this->assertNotContains('sering_terlambat', $rows()['555']['flags'], '2 late arrivals: below the limit of 3');

        MasterEntry::where('code', 'LATE_MAX_PER_MONTH')->update(['attrs' => ['value' => 1, 'unit' => 'kali / bulan']]);
        MasterSettings::flush();

        $this->assertContains('sering_terlambat', $rows()['555']['flags']);
    }

    public function test_page_scope_and_profile(): void
    {
        $this->roster('111', 'BUDI', '2026-10-05', 'P40', 'PAGI', 'CGK');
        $this->roster('222', 'SARI', '2026-10-05', 'P40', 'PAGI', 'SUB');
        DB::table('job_crew')->insert(['jobable_type' => 'aircraft_cleaning', 'jobable_id' => 1, 'employee_ref' => '111', 'man_hour' => 2.5, 'work_date' => '2026-10-05', 'station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);

        $manager = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $manager->assignRole(RoleHelper::MANAGER);
        Livewire::actingAs($manager)->test(Index::class)->set('date', '2026-10-01')->assertSee('BUDI')->assertSee('SARI')
            ->call('pick', '111')->assertSee('Man hours dikerjakan')->assertSee('2.5');

        $pic = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'station' => 'SUB']);
        $pic->assignRole(RoleHelper::PIC_CABIN);
        Livewire::actingAs($pic)->test(Index::class)->set('date', '2026-10-01')->assertSee('SARI')->assertDontSee('BUDI');
        Livewire::actingAs($pic)->test(Index::class)->set('file', null)->call('importFile')->assertForbidden();

        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->actingAs($nobody)->get('/attendance')->assertForbidden();
        $this->actingAs($manager)->get('/attendance')->assertOk();
    }
}
