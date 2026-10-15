<?php

namespace Tests\Feature;

use App\Livewire\Modules\DmiLog\Index as DmiIndex;
use App\Livewire\Modules\WoLog\Index as WoIndex;
use App\Models\DmiLog;
use App\Models\SheetSyncLog;
use App\Models\User;
use App\Models\WoLog;
use App\Services\Dja\UnplannedSheetPusher;
use App\Services\Leader\UnplannedSheetWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuickUnplannedEntryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole(Role::findOrCreate('Super Admin'));

        return $u;
    }

    public function test_pasted_lines_become_closed_unplanned_logs_and_duplicates_are_skipped(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(WoIndex::class)
            ->set('quickText', "REG\tWO NO\tDESCRIPTION\tACT STA\nPK-ABC\tWO-Q1\tFix seat\tcgk\nPK-ABC\tWO-Q1\tdup\tcgk\nPK-DEF\tWO-Q2\tFix lamp\tsub")
            ->call('saveQuick');

        $this->assertSame(2, WoLog::count());
        $wo = WoLog::where('wo_number', 'WO-Q1')->first();
        $this->assertNull($wo->dja_id);
        $this->assertSame('Closed', $wo->status);
        $this->assertSame('CGK', $wo->act_station);
    }

    public function test_dmi_quick_entry_keeps_optional_fields_empty(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(DmiIndex::class)
            ->set('quickText', 'PK-ABC;DMI-Q1;CAT A;Torn carpet;;SUB;PN-9')
            ->call('saveQuick');

        $d = DmiLog::where('dmi_number', 'DMI-Q1')->first();
        $this->assertSame('PN-9', $d->pn_required);
        $this->assertSame('SUB', $d->act_station);
        $this->assertNull($d->status_wo);
    }

    public function test_failed_sheet_write_is_logged_silently(): void
    {
        $writer = Mockery::mock(UnplannedSheetWriter::class);
        $writer->shouldReceive('upsert')->andReturn(false);
        $log = WoLog::create(['wo_number' => 'WO-F1', 'aircraft_registration' => 'PK-ABC', 'status' => 'Closed', 'date' => '2026-10-13']);

        $this->assertFalse((new UnplannedSheetPusher($writer))->push($log));
        $entry = SheetSyncLog::where('event', 'failed')->first();
        $this->assertSame('WO-F1', $entry->doc_no);
        $this->assertNull($entry->resolved_at);

        $ok = Mockery::mock(UnplannedSheetWriter::class);
        $ok->shouldReceive('upsert')->andReturn(true);
        $this->assertSame(1, (new UnplannedSheetPusher($ok))->retryFailed());
        $this->assertSame('healed', $entry->fresh()->event);
    }
}
