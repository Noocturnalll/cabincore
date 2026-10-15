<?php

namespace Tests\Feature;

use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Services\WhatsApp\CodProductionReport;
use App\Services\WhatsApp\UniversalWhatsAppReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodProductionReportTest extends TestCase
{
    use RefreshDatabase;

    private function dja(string $task, string $station, string $type = 'R01/WO'): DailyJobAssignment
    {
        return DailyJobAssignment::create([
            'task_id' => $task, 'date' => '2026-10-13', 'job_type' => $type,
            'aircraft_registration' => 'PK-ABC', 'description' => 'x', 'station' => $station,
        ]);
    }

    public function test_the_message_follows_the_cod_format(): void
    {
        foreach ([['W1', 'CGK', 'Closed'], ['W2', 'CGK', 'Open'], ['W3', 'SUB', 'Closed']] as [$no, $sta, $status]) {
            WoLog::create(['dja_id' => $this->dja($no, $sta)->id, 'wo_number' => $no, 'aircraft_registration' => 'PK-ABC', 'plan_station' => $sta, 'status' => $status, 'date' => '2026-10-13']);
        }
        NsrdiLog::create(['dja_id' => $this->dja('N1', 'KOE', 'AOC/NSRDI')->id, 'nsrdi_number' => 'N1', 'aircraft_registration' => 'PK-ABC', 'plan_station' => 'KOE', 'status' => 'Open',
            'hold_reason_category' => 'IRR', 'plan_date' => '2026-10-13', 'category' => 'CBM']);
        NsrdiLog::create(['aircraft_registration' => 'PK-ABC', 'nsrdi_number' => 'U1', 'act_station' => 'HLP', 'plan_station' => 'CGK', 'status' => 'Closed', 'plan_date' => '2026-10-13', 'category' => 'CBM']);
        NsrdiLog::create(['aircraft_registration' => 'PK-ABC', 'nsrdi_number' => 'U2', 'act_station' => 'HLP', 'status' => 'Closed', 'plan_date' => '2026-10-13', 'category' => 'CBM']);

        $text = app(CodProductionReport::class)->text('2026-10-13', '2026-10-13');

        $this->assertStringContainsString('> CABIN ON-DUTY PRODUCTION 13 OCT 2026', $text);
        $this->assertStringContainsString('> DJA R01/WO', $text);
        $this->assertStringContainsString('* `CGK:` 2 (C:1 R:1)', $text);
        $this->assertStringContainsString('* `SUB:` 1 (C:1 R:-)', $text);
        $this->assertStringContainsString('* `HLP:` - (C:- R:-)', $text);
        $this->assertStringContainsString("DEPLOY: 3\nCLOSED: 2\nOPEN  : 1", $text);
        $this->assertStringContainsString('`IRR:` 1', $text);
        $this->assertStringContainsString('`AUTHOR:` -', $text);
        // unplanned NSRDI is counted by ACTUAL station, both on HLP even though one has plan station CGK
        $this->assertMatchesRegularExpression('/> UNPLANED NSRDIL\n.*\* `CGK:` -\n\* `HLP:` 2\n/s', $text);
        $this->assertStringContainsString("CLOSED: 2\n", $text);
        $this->assertStringContainsString('Cabin On Duty', $text);
    }

    public function test_the_title_is_yesterday_when_asked_for_today(): void
    {
        Carbon::setTestNow('2026-10-21 09:00:00');

        $r = app(UniversalWhatsAppReportService::class)->generateReport('cbm', ['start_date' => '2026-10-21']);
        $this->assertStringContainsString('CABIN ON-DUTY PRODUCTION 20 OCT 2026', $r['report_text']);

        // a past day chosen on screen stays as it is
        $r = app(UniversalWhatsAppReportService::class)->generateReport('cbm', ['start_date' => '2026-10-13']);
        $this->assertStringContainsString('CABIN ON-DUTY PRODUCTION 13 OCT 2026', $r['report_text']);

        Carbon::setTestNow();
    }

    public function test_painting_nsrdi_is_counted_with_cbm_planned_and_unplanned(): void
    {
        NsrdiLog::create(['dja_id' => $this->dja('P1', 'CGK', 'AOC/NSRDI')->id, 'nsrdi_number' => 'P1', 'aircraft_registration' => 'PK-ABC', 'plan_station' => 'CGK', 'status' => 'Closed', 'plan_date' => '2026-10-13', 'category' => 'PAINTING']);
        NsrdiLog::create(['dja_id' => $this->dja('C1', 'CGK', 'AOC/NSRDI')->id, 'nsrdi_number' => 'C1', 'aircraft_registration' => 'PK-ABC', 'plan_station' => 'CGK', 'status' => 'Open', 'plan_date' => '2026-10-13', 'category' => 'CBM']);
        NsrdiLog::create(['aircraft_registration' => 'PK-ABC', 'nsrdi_number' => 'PU1', 'act_station' => 'SUB', 'status' => 'Closed', 'plan_date' => '2026-10-13', 'category' => 'PAINTING']);

        $text = app(CodProductionReport::class)->text('2026-10-13', '2026-10-13');

        $this->assertStringContainsString('* `CGK:` 2 (C:1 R:1)', $text);   // painting + cbm in the same NSRDI list
        $this->assertStringContainsString('* `SUB:` 1', $text);              // unplanned painting NSRDI by actual station
    }

    public function test_reason_open_counts_open_wo_and_nsrdi_but_not_dmi(): void
    {
        WoLog::create(['dja_id' => $this->dja('RW', 'CGK')->id, 'wo_number' => 'RW', 'aircraft_registration' => 'PK-ABC', 'plan_station' => 'CGK', 'status' => 'Open', 'hold_reason_category' => 'LT', 'date' => '2026-10-13']);
        NsrdiLog::create(['dja_id' => $this->dja('RN', 'CGK', 'AOC/NSRDI')->id, 'nsrdi_number' => 'RN', 'aircraft_registration' => 'PK-ABC', 'plan_station' => 'CGK', 'status' => 'Open', 'hold_reason_category' => 'LT', 'plan_date' => '2026-10-13', 'category' => 'CBM']);
        DmiLog::create(['dja_id' => $this->dja('RD', 'CGK', 'DMI')->id, 'dmi_number' => 'RD', 'aircraft_registration' => 'PK-ABC', 'plan_station' => 'CGK', 'status' => 'Open', 'hold_reason_category' => 'LT', 'date' => '2026-10-13']);

        $text = app(CodProductionReport::class)->text('2026-10-13', '2026-10-13');

        $this->assertStringContainsString('`LT:` 2', $text);
    }
}
