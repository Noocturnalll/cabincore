<?php

namespace Tests\Feature;

use App\Models\CapacityTarget;
use App\Models\DailyJobAssignment;
use App\Models\NsrdiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NsrdiImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_nsrdi_import_command_imports_full_workbook(): void
    {
        $filePath = base_path('files/NSRDI Closure 9 - 15 Oct 2026 Update.xlsx');

        $this->artisan('nsrdi:import', ['file' => $filePath])
            ->expectsOutputToContain('Import NSRDI selesai dengan sukses!')
            ->assertSuccessful();

        $this->assertSame(282, NsrdiLog::count());
        $this->assertSame(209, NsrdiLog::whereNotNull('dja_id')->count());
        $this->assertSame(73, NsrdiLog::whereNull('dja_id')->count());

        // Verify status normalization
        $this->assertSame(232, NsrdiLog::where('status', 'Closed')->count());
        $this->assertSame(50, NsrdiLog::where('status', 'Open')->count());

        // Verify category separation
        $this->assertSame(272, NsrdiLog::where('category', 'CBM')->count());
        $this->assertSame(10, NsrdiLog::where('category', 'PAINTING')->count());

        // Verify CapacityTarget sync
        $this->assertSame(10, CapacityTarget::where('aoc', 'JT')->value('target_nsrdi'));
        $this->assertSame(15, CapacityTarget::where('aoc', 'ID')->value('target_nsrdi'));
        $this->assertSame(18, CapacityTarget::where('aoc', 'IU')->value('target_nsrdi'));
        $this->assertSame(12, CapacityTarget::where('aoc', 'IW')->value('target_nsrdi'));
    }

    public function test_nsrdi_import_dry_run_does_not_persist(): void
    {
        $filePath = base_path('files/NSRDI Closure 9 - 15 Oct 2026 Update.xlsx');

        $this->artisan('nsrdi:import', ['file' => $filePath, '--dry-run' => true])
            ->expectsOutputToContain('[DRY RUN] Import NSRDI selesai dengan sukses!')
            ->assertSuccessful();

        $this->assertSame(0, NsrdiLog::count());
        $this->assertSame(0, DailyJobAssignment::count());
    }
}
