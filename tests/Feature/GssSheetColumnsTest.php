<?php

namespace Tests\Feature;

use App\Models\DmiLog;
use App\Models\WoLog;
use App\Services\Dja\DjaPersister;
use App\Services\Dja\DjaRowMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GssSheetColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_captures_extra_dja_wo_columns(): void
    {
        $h = ['WG', 'AC REG', 'WO', 'CATEGORY', 'DESCRIPTION', 'STATUS', 'RII', 'ETOPS', 'SITE', 'DUE DATE WO', 'CLASS WO', 'PLANNER', 'PPC ONDUTY', 'HIGHLIGHT', 'PLAN DATE'];
        $row = ['CBM', 'PK-ABC', 'WO-1', 'CABIN', 'Replace seat', 'Closed', 'Y', 'N', 'CGK', '2026-10-20', 'A', 'Budi', 'Sari', 'urgent', '2026-10-13'];
        $m = (new DjaRowMapper)->map('DJA', $row, array_flip($h));

        $this->assertSame('Y', $m['log']['rii']);
        $this->assertSame('CGK', $m['log']['site']);
        $this->assertSame('Budi', $m['log']['planner']);

        (new DjaPersister)->store($m, null);
        $this->assertSame('Sari', WoLog::where('wo_number', 'WO-1')->first()->ppc_onduty);
    }

    public function test_status_dmi_drives_log_and_status_wo_stays_separate(): void
    {
        $h = ['AC REG', 'DMI NO', 'DESCRIPTION', 'STATUS DMI', 'STATUS WO', 'WG', 'DEFER TYPE', 'PICKLIST NUMBER'];
        $row = ['PK-ABC', 'DMI-9', 'Torn carpet', 'Closed', '', 'CBM', 'MEL', 'PL-1'];
        (new DjaPersister)->store((new DjaRowMapper)->map('DJA DMI', $row, array_flip($h)), null);

        $d = DmiLog::where('dmi_number', 'DMI-9')->first();
        $this->assertSame('Closed', $d->status);
        $this->assertNull($d->status_wo);
        $this->assertSame('MEL', $d->defer_type);
        $this->assertSame('PL-1', $d->picklist_number);
    }
}
