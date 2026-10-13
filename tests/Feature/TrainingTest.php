<?php

namespace Tests\Feature;

use App\Livewire\Modules\Hr\Training;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeTraining;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TrainingTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('hr.view');
        Permission::findOrCreate('hr.manage');

        $this->hrUser = User::factory()->create();
        $this->hrUser->givePermissionTo(['hr.view', 'hr.manage']);

        $this->guestUser = User::factory()->create();
    }

    public function test_hr_user_can_access_training_page(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('hr.training'))
            ->assertOk()
            ->assertSee('Database Training Cabin Maintenance');
    }

    public function test_user_without_permission_cannot_access_training(): void
    {
        $this->actingAs($this->guestUser)
            ->get(route('hr.training'))
            ->assertForbidden();
    }

    public function test_training_component_renders_records_and_filters(): void
    {
        EmployeeTraining::create([
            'employee_nik' => '99001',
            'employee_name' => 'Budi Santoso',
            'station' => 'CGK',
            'job_title' => 'Cabin Technician',
            'company' => 'BAT',
            'category' => 'Mandatory Training',
            'course_name' => 'Initial Human Factors',
            'course_date' => '2024-05-10',
            'expiry_date' => '2026-05-10',
            'status' => 'valid',
        ]);

        EmployeeTraining::create([
            'employee_nik' => '99002',
            'employee_name' => 'Siti Rahma',
            'station' => 'SUB',
            'job_title' => 'Cabin Lead',
            'company' => 'AAS',
            'category' => 'Type Rating & Systems',
            'course_name' => 'B737 Classic & NG',
            'status' => 'scheduled',
            'schedule_status' => 'TERJADWAL',
        ]);

        $division = Division::create(['name' => 'Cabin Maintenance', 'status' => 'Aktif']);
        $employee = Employee::create([
            'nik' => '99001',
            'name' => 'Budi Santoso',
            'division_id' => $division->id,
            'status' => 'Aktif',
        ]);
        EmployeeTraining::where('employee_nik', '99001')->update(['employee_id' => $employee->id]);

        Livewire::actingAs($this->hrUser)->test(Training::class)
            ->assertSee('Budi Santoso')
            ->assertSee('Siti Rahma')
            ->set('stationFilter', 'CGK')
            ->assertSee('Budi Santoso')
            ->assertDontSee('Siti Rahma')
            ->set('stationFilter', '')
            ->set('divisionFilter', (string) $division->id)
            ->assertSee('Budi Santoso')
            ->assertDontSee('Siti Rahma')
            ->set('divisionFilter', '')
            ->set('statusFilter', 'scheduled')
            ->assertSee('Siti Rahma')
            ->assertDontSee('Budi Santoso');
    }

    public function test_can_open_employee_training_transcript_modal(): void
    {
        EmployeeTraining::create([
            'employee_nik' => '99001',
            'employee_name' => 'Budi Santoso',
            'station' => 'CGK',
            'category' => 'Mandatory Training',
            'course_name' => 'Safety Management System (SMS)',
            'status' => 'valid',
        ]);

        Livewire::actingAs($this->hrUser)->test(Training::class)
            ->call('openDetail', '99001')
            ->assertSet('isDetailOpen', true)
            ->assertSet('selectedEmployeeName', 'Budi Santoso')
            ->assertSee('Safety Management System (SMS)')
            ->call('closeDetail')
            ->assertSet('isDetailOpen', false);
    }

    public function test_qualification_category_filter_and_tabs(): void
    {
        EmployeeTraining::create([
            'employee_nik' => '99001',
            'employee_name' => 'Budi Santoso',
            'station' => 'CGK',
            'category' => 'Mandatory Training',
            'course_name' => 'Initial Human Factors',
            'status' => 'valid',
        ]);

        EmployeeTraining::create([
            'employee_nik' => '99002',
            'employee_name' => 'Siti Rahma',
            'station' => 'SUB',
            'category' => 'Type Rating & Systems',
            'course_name' => 'B737 Classic & NG',
            'status' => 'valid',
        ]);

        Livewire::actingAs($this->hrUser)->test(Training::class)
            ->assertSee('RUMPUN KUALIFIKASI:')
            ->assertSee('Mandatory Training')
            ->assertSee('Type Rating & Systems')
            ->call('setCategory', 'Mandatory Training')
            ->assertSee('Initial Human Factors')
            ->assertDontSee('B737 Classic & NG')
            ->call('setCategory', 'Type Rating & Systems')
            ->assertSee('B737 Classic & NG')
            ->assertDontSee('Initial Human Factors')
            ->call('setCategory', '')
            ->assertSee('Initial Human Factors')
            ->assertSee('B737 Classic & NG');
    }

    public function test_summary_and_pivot_tab_renders_matrices(): void
    {
        EmployeeTraining::create([
            'employee_nik' => '99001',
            'employee_name' => 'Budi Santoso',
            'station' => 'CGK',
            'category' => 'Mandatory Training',
            'course_name' => 'Human Factors',
            'status' => 'valid',
        ]);

        EmployeeTraining::create([
            'employee_nik' => '99002',
            'employee_name' => 'Siti Rahma',
            'station' => 'SUB',
            'category' => 'Type Rating & Systems',
            'course_name' => 'A330 Systems',
            'status' => 'expired',
        ]);

        Livewire::actingAs($this->hrUser)->test(Training::class)
            ->assertSee('Pivot Kualifikasi')
            ->call('setTab', 'pivot')
            ->assertSet('tab', 'pivot')
            ->assertSee('Matriks Pivot 1: Kualifikasi per Station Kerja')
            ->assertSee('Matriks Pivot 2: Rumpun Kualifikasi Pelatihan')
            ->assertSee('Matriks Pivot 3: Kualifikasi per Divisi Unit Kerja')
            ->assertSee('CGK')
            ->assertSee('SUB')
            ->assertSee('Mandatory Training');
    }

    public function test_ctrl_f_search_input_and_badge_are_present(): void
    {
        Livewire::actingAs($this->hrUser)->test(Training::class)
            ->assertSeeHtml('id="training-search-input"')
            ->assertSeeHtml('Ctrl + F');
    }
}
