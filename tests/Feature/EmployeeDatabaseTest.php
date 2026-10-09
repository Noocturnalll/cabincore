<?php

namespace Tests\Feature;

use App\Livewire\Modules\Hr\Employees;
use App\Models\Division;
use App\Models\Employee;
use App\Models\User;
use App\Support\ExpiryStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $permissions, ?Division $division = null): User
    {
        foreach (['hr.view', 'hr.manage', 'hr.view_all'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $user = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'division_id' => $division?->id]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function division(string $name): Division
    {
        return Division::create(['name' => $name, 'status' => 'Aktif']);
    }

    public function test_contract_window_yellow_at_two_months_red_at_one(): void
    {
        $today = Carbon::parse('2026-10-08');

        $this->assertSame(ExpiryStatus::OK, ExpiryStatus::for(Carbon::parse('2026-12-09'), 2, 1, $today));
        $this->assertSame(ExpiryStatus::YELLOW, ExpiryStatus::for(Carbon::parse('2026-12-08'), 2, 1, $today));
        $this->assertSame(ExpiryStatus::YELLOW, ExpiryStatus::for(Carbon::parse('2026-11-09'), 2, 1, $today));
        $this->assertSame(ExpiryStatus::RED, ExpiryStatus::for(Carbon::parse('2026-11-08'), 2, 1, $today));
        $this->assertSame(ExpiryStatus::RED, ExpiryStatus::for(Carbon::parse('2026-10-08'), 2, 1, $today));
        $this->assertSame(ExpiryStatus::EXPIRED, ExpiryStatus::for(Carbon::parse('2026-10-07'), 2, 1, $today));
        $this->assertSame(ExpiryStatus::NONE, ExpiryStatus::for(null, 2, 1, $today));
    }

    public function test_permanent_staff_have_no_contract_marker_and_passport_uses_six_months(): void
    {
        $permanent = new Employee(['contract_type' => 'PKWTT', 'contract_end' => now()->subYear()]);
        $this->assertSame(ExpiryStatus::NONE, $permanent->contractStatus());

        $e = new Employee(['passport_expiry' => now()->addMonths(5)]);
        $this->assertSame(ExpiryStatus::YELLOW, $e->passportStatus());
        $e = new Employee(['passport_expiry' => now()->addMonths(2)]);
        $this->assertSame(ExpiryStatus::RED, $e->passportStatus());
        $e = new Employee(['passport_expiry' => now()->addMonths(8)]);
        $this->assertSame(ExpiryStatus::OK, $e->passportStatus());
    }

    public function test_page_requires_permission(): void
    {
        $this->actingAs($this->user([]))->get('/hr/employees')->assertForbidden();
        $this->actingAs($this->user(['hr.view']))->get('/hr/employees')->assertOk();
    }

    public function test_viewer_cannot_change_data(): void
    {
        $div = $this->division('Cabin');
        $viewer = $this->user(['hr.view'], $div);

        Livewire::actingAs($viewer)->test(Employees::class)->call('create')->assertForbidden();
    }

    public function test_division_admin_is_limited_to_own_division(): void
    {
        $mine = $this->division('Cabin');
        $other = $this->division('Painting');
        $admin = $this->user(['hr.view', 'hr.manage'], $mine);

        $foreign = Employee::create(['nik' => 'X1', 'name' => 'Orang Lain', 'division_id' => $other->id, 'contract_type' => 'PKWTT']);

        $c = Livewire::actingAs($admin)->test(Employees::class)->assertDontSee('Orang Lain');
        try {
            $c->call('edit', $foreign->id);
            $this->fail('Karyawan divisi lain seharusnya tidak dapat dibuka.');
        } catch (ModelNotFoundException) {
            $this->addToAssertionCount(1);
        }

        $c->call('create')
            ->set('nik', 'N1')->set('name', 'Budi')->set('division_id', $other->id)->set('contract_type', 'PKWTT')
            ->call('save')->assertHasErrors('division_id');

        $c->set('division_id', $mine->id)->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('employees', ['nik' => 'N1', 'division_id' => $mine->id]);
    }

    public function test_pkwt_requires_end_date_and_passport_requires_expiry(): void
    {
        $div = $this->division('Cabin');
        $admin = $this->user(['hr.view', 'hr.manage', 'hr.view_all'], $div);

        Livewire::actingAs($admin)->test(Employees::class)
            ->call('create')
            ->set('nik', 'N2')->set('name', 'Sari')->set('division_id', $div->id)->set('contract_type', 'PKWT')
            ->call('save')->assertHasErrors('contract_end')
            ->set('contract_end', now()->addMonth()->toDateString())->set('passport_no', 'A123')
            ->call('save')->assertHasErrors('passport_expiry')
            ->set('passport_expiry', now()->addYear()->toDateString())
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('employees', ['nik' => 'N2', 'passport_no' => 'A123']);
    }

    public function test_expiry_filter_lists_only_soon_expiring(): void
    {
        $div = $this->division('Cabin');
        $admin = $this->user(['hr.view', 'hr.view_all']);
        Employee::create(['nik' => 'A', 'name' => 'Segera Habis', 'division_id' => $div->id, 'contract_type' => 'PKWT', 'contract_end' => now()->addDays(20)]);
        Employee::create(['nik' => 'B', 'name' => 'Masih Lama', 'division_id' => $div->id, 'contract_type' => 'PKWT', 'contract_end' => now()->addYear()]);

        Livewire::actingAs($admin)->test(Employees::class)
            ->set('expiryFilter', 'contract')
            ->assertSee('Segera Habis')->assertDontSee('Masih Lama');
    }
}
