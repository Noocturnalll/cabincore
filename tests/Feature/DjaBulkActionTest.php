<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\DailyJobAssigment\Index;
use App\Models\DailyJobAssignment;
use App\Models\DjaSyncReview;
use App\Models\User;
use App\Models\WoLog;
use App\Services\Dja\ClassificationMemory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DjaBulkActionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate(RoleHelper::SUPER_ADMIN, 'web');
        $user = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $user->assignRole(RoleHelper::SUPER_ADMIN);

        return $user;
    }

    public function test_bulk_accept_reviews(): void
    {
        $admin = $this->admin();

        $rev1 = DjaSyncReview::create([
            'row_key' => DjaSyncReview::keyFor('WO', 'WO-BULK-01', 'PK-GQA', 'Cabin carpet check row 1-5'),
            'tab' => 'WO',
            'kind' => 'wo',
            'task_id' => 'WO-BULK-01',
            'aircraft_registration' => 'PK-GQA',
            'description' => 'Cabin carpet check row 1-5',
            'bucket' => 'review',
            'rule' => 'keyword.weak',
            'reason' => 'Perlu cek manual',
            'payload' => [
                'kind' => 'wo',
                'tab' => 'WO',
                'task_id' => 'WO-BULK-01',
                'ac_reg' => 'PK-GQA',
                'description' => 'Cabin carpet check row 1-5',
                'job_type' => 'R01/WO',
                'station' => 'BTH',
                'log' => [
                    'aircraft_registration' => 'PK-GQA',
                    'description' => 'Cabin carpet check row 1-5',
                ],
            ],
            'last_seen_at' => now(),
        ]);

        $rev2 = DjaSyncReview::create([
            'row_key' => DjaSyncReview::keyFor('WO', 'WO-BULK-02', 'PK-GQA', 'Passenger seat tray repair'),
            'tab' => 'WO',
            'kind' => 'wo',
            'task_id' => 'WO-BULK-02',
            'aircraft_registration' => 'PK-GQA',
            'description' => 'Passenger seat tray repair',
            'bucket' => 'review',
            'rule' => 'keyword.weak',
            'reason' => 'Perlu cek manual',
            'payload' => [
                'kind' => 'wo',
                'tab' => 'WO',
                'task_id' => 'WO-BULK-02',
                'ac_reg' => 'PK-GQA',
                'description' => 'Passenger seat tray repair',
                'job_type' => 'R01/WO',
                'station' => 'BTH',
                'log' => [
                    'aircraft_registration' => 'PK-GQA',
                    'description' => 'Passenger seat tray repair',
                ],
            ],
            'last_seen_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('activeTab', 'review')
            ->set('selectedReviews', [(string) $rev1->id, (string) $rev2->id])
            ->call('bulkAcceptReviews');

        $this->assertSame('accepted', $rev1->fresh()->decision);
        $this->assertSame('accepted', $rev2->fresh()->decision);
        $this->assertTrue(DailyJobAssignment::where('task_id', 'WO-BULK-01')->exists());
        $this->assertTrue(DailyJobAssignment::where('task_id', 'WO-BULK-02')->exists());

        $memory = app(ClassificationMemory::class);
        $this->assertSame(ClassificationMemory::CBM, $memory->lookup('wo', null, 'Cabin carpet check row 1-5')['category']);
        $this->assertSame(ClassificationMemory::CBM, $memory->lookup('wo', null, 'Passenger seat tray repair')['category']);
    }

    public function test_bulk_reject_reviews(): void
    {
        $admin = $this->admin();

        $rev1 = DjaSyncReview::create([
            'row_key' => DjaSyncReview::keyFor('WO', 'WO-BULK-REJ-01', 'PK-GQA', 'Exterior deep cleaning'),
            'tab' => 'WO',
            'kind' => 'wo',
            'task_id' => 'WO-BULK-REJ-01',
            'aircraft_registration' => 'PK-GQA',
            'description' => 'Exterior deep cleaning',
            'bucket' => 'review',
            'rule' => 'keyword.weak',
            'reason' => 'Perlu cek manual',
            'payload' => [],
            'last_seen_at' => now(),
        ]);

        $rev2 = DjaSyncReview::create([
            'row_key' => DjaSyncReview::keyFor('WO', 'WO-BULK-REJ-02', 'PK-GQA', 'Cockpit overhead P5 panel fix'),
            'tab' => 'WO',
            'kind' => 'wo',
            'task_id' => 'WO-BULK-REJ-02',
            'aircraft_registration' => 'PK-GQA',
            'description' => 'Cockpit overhead P5 panel fix',
            'bucket' => 'review',
            'rule' => 'keyword.weak',
            'reason' => 'Perlu cek manual',
            'payload' => [],
            'last_seen_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('activeTab', 'review')
            ->set('selectedReviews', [(string) $rev1->id, (string) $rev2->id])
            ->call('bulkRejectReviews');

        $this->assertSame('rejected', $rev1->fresh()->decision);
        $this->assertSame('rejected', $rev2->fresh()->decision);
        $this->assertFalse(DailyJobAssignment::where('task_id', 'WO-BULK-REJ-01')->exists());

        $memory = app(ClassificationMemory::class);
        $this->assertSame(ClassificationMemory::LINE, $memory->lookup('wo', null, 'Exterior deep cleaning')['category']);
    }

    public function test_bulk_remove_from_dja(): void
    {
        $admin = $this->admin();

        $dja1 = DailyJobAssignment::create([
            'task_id' => 'DJA-LOLOS-01',
            'aircraft_registration' => 'PK-LJR',
            'date' => now()->format('Y-m-d'),
            'job_type' => 'R01/WO',
            'description' => 'Vacuum toilet water separator inspect',
            'station' => 'CGK',
        ]);

        $dja2 = DailyJobAssignment::create([
            'task_id' => 'DJA-LOLOS-02',
            'aircraft_registration' => 'PK-LJR',
            'date' => now()->format('Y-m-d'),
            'job_type' => 'R01/WO',
            'description' => 'Deep cleaning exterior fuselage',
            'station' => 'CGK',
        ]);

        $log1 = WoLog::create([
            'dja_id' => $dja1->id,
            'wo_number' => 'DJA-LOLOS-01',
            'aircraft_registration' => 'PK-LJR',
            'description' => 'Vacuum toilet water separator inspect',
            'is_submitted' => false,
        ]);

        $log2 = WoLog::create([
            'dja_id' => $dja2->id,
            'wo_number' => 'DJA-LOLOS-02',
            'aircraft_registration' => 'PK-LJR',
            'description' => 'Deep cleaning exterior fuselage',
            'is_submitted' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('activeTab', 'data')
            ->set('selectedDjas', [(string) $dja1->id, (string) $dja2->id])
            ->call('bulkRemoveFromDja');

        // DJA records must be deleted
        $this->assertSoftDeleted('daily_job_assignments', ['id' => $dja1->id]);
        $this->assertSoftDeleted('daily_job_assignments', ['id' => $dja2->id]);

        // Unsubmitted logs must be removed
        $this->assertDatabaseMissing('wo_logs', ['id' => $log1->id]);
        $this->assertDatabaseMissing('wo_logs', ['id' => $log2->id]);

        // Moved to rejected reviews to prevent re-ingest
        $rev1 = DjaSyncReview::where('task_id', 'DJA-LOLOS-01')->first();
        $this->assertNotNull($rev1);
        $this->assertSame('rejected', $rev1->decision);
        $this->assertSame('manual.removed_from_dja', $rev1->rule);

        $rev2 = DjaSyncReview::where('task_id', 'DJA-LOLOS-02')->first();
        $this->assertNotNull($rev2);
        $this->assertSame('rejected', $rev2->decision);

        // Remembered as LINE
        $memory = app(ClassificationMemory::class);
        $this->assertSame(ClassificationMemory::LINE, $memory->lookup('wo', null, 'Vacuum toilet water separator inspect')['category']);
    }

    public function test_select_all_toggles(): void
    {
        $admin = $this->admin();

        $dja1 = DailyJobAssignment::create([
            'task_id' => 'TASK-01',
            'aircraft_registration' => 'PK-LJR',
            'date' => now()->format('Y-m-d'),
            'job_type' => 'R01/WO',
            'description' => 'Sample task 1',
            'station' => 'CGK',
        ]);

        $dja2 = DailyJobAssignment::create([
            'task_id' => 'TASK-02',
            'aircraft_registration' => 'PK-LJR',
            'date' => now()->format('Y-m-d'),
            'job_type' => 'R01/WO',
            'description' => 'Sample task 2',
            'station' => 'CGK',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('activeTab', 'data')
            ->set('selectAllDjas', true)
            ->assertSet('selectedDjas', [(string) $dja1->id, (string) $dja2->id])
            ->set('selectAllDjas', false)
            ->assertSet('selectedDjas', []);
    }
}
