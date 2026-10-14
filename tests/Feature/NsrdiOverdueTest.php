<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\NsrdiOverdue\Index as NsrdiOverdueIndex;
use App\Models\NsrdiLog;
use App\Models\NsrdiOverdue;
use App\Models\User;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class NsrdiOverdueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create([
            'is_default_password' => false,
            'status' => 'active',
            'station' => 'CGK',
        ]);
        $user->assignRole(RoleHelper::SUPER_ADMIN);

        return $user;
    }

    public function test_nsrdi_overdue_page_renders_with_tabs(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        NsrdiOverdue::create([
            'operator' => 'JT',
            'aircraft_registration' => 'PK-LJQ',
            'nsrdi_number' => 'E0400817',
            'status' => 'Open',
            'due_date' => now()->addDays(5),
            'description' => 'Cover window broken',
        ]);

        NsrdiOverdue::create([
            'operator' => 'ID',
            'aircraft_registration' => 'PK-LAF',
            'nsrdi_number' => 'NID006909',
            'status' => 'Closed',
            'due_date' => now()->subDays(2),
            'description' => 'Blind roller fixed',
            'closed_at' => now(),
        ]);

        Livewire::test(NsrdiOverdueIndex::class)
            ->assertSee('Tab Open / Outstanding')
            ->assertSee('Tab Closed')
            ->assertSee('Semua Data')
            ->assertSee('E0400817')
            ->assertDontSee('NID006909')
            ->call('setTab', 'closed')
            ->assertSee('NID006909')
            ->assertDontSee('E0400817')
            ->call('setTab', 'all')
            ->assertSee('E0400817')
            ->assertSee('NID006909');
    }

    public function test_sync_data_marks_overdue_as_closed_when_found_in_nsrdi_log(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        // NsrdiLog has closed defect
        NsrdiLog::create([
            'nsrdi_number' => 'DEF-9988',
            'status' => 'Closed',
            'aircraft_registration' => 'PK-LHF',
            'description' => 'Seat belt replaced',
        ]);

        // Overdue has open defect with same number
        $overdue = NsrdiOverdue::create([
            'operator' => 'JT',
            'aircraft_registration' => 'PK-LHF',
            'nsrdi_number' => 'DEF-9988',
            'status' => 'Open',
            'due_date' => now()->addDays(3),
        ]);

        Livewire::test(NsrdiOverdueIndex::class)
            ->call('syncData')
            ->assertDispatched('notify');

        $this->assertSame('Closed', $overdue->fresh()->status);
        $this->assertNotNull($overdue->fresh()->closed_at);
    }

    public function test_update_status_modal_works(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $overdue = NsrdiOverdue::create([
            'operator' => 'JT',
            'aircraft_registration' => 'PK-LJQ',
            'nsrdi_number' => 'DEF-1122',
            'status' => 'Open',
        ]);

        Livewire::test(NsrdiOverdueIndex::class)
            ->call('openEditModal', $overdue->id)
            ->set('editStatus', 'Closed')
            ->set('editStatusFinal', 'REPLACED')
            ->set('editRemarks', 'Closed manually by leader')
            ->call('updateOverdue')
            ->assertDispatched('notify');

        $fresh = $overdue->fresh();
        $this->assertSame('Closed', $fresh->status);
        $this->assertSame('REPLACED', $fresh->status_final);
        $this->assertSame('Closed manually by leader', $fresh->remarks);
        $this->assertNotNull($fresh->closed_at);
    }

    public function test_export_data_downloads_excel_file(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        NsrdiOverdue::create([
            'operator' => 'JT',
            'aircraft_registration' => 'PK-LJQ',
            'nsrdi_number' => 'E0400817',
            'status' => 'Open',
            'due_date' => now()->addDays(5),
            'description' => 'Cover window broken',
            'part_number' => '5862-00',
        ]);

        Livewire::test(NsrdiOverdueIndex::class)
            ->call('exportData')
            ->assertFileDownloaded();
    }

    public function test_import_data_handles_file_upload(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $path = base_path('files/NSRDI WEEK 1 OCT.xlsx');
        if (! file_exists($path)) {
            $this->markTestSkipped('Sample file not found');
        }

        $file = UploadedFile::fake()->createWithContent('nsrdi.xlsx', file_get_contents($path));

        Livewire::test(NsrdiOverdueIndex::class)
            ->set('file', $file)
            ->call('importData')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertGreaterThan(0, NsrdiOverdue::count());
    }

    public function test_nsrdi_overdue_auto_closes_when_nsrdi_log_is_saved_as_closed(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $overdue = NsrdiOverdue::create([
            'operator' => 'JT',
            'aircraft_registration' => 'PK-LHF',
            'nsrdi_number' => 'AUTO-CLOSE-123',
            'status' => 'Open',
            'due_date' => now()->addDays(2),
        ]);

        NsrdiLog::create([
            'nsrdi_number' => 'AUTO-CLOSE-123',
            'status' => 'Closed',
            'aircraft_registration' => 'PK-LHF',
            'description' => 'Tray table fixed',
        ]);

        $this->assertSame('Closed', $overdue->fresh()->status);
        $this->assertNotNull($overdue->fresh()->closed_at);
    }
}
