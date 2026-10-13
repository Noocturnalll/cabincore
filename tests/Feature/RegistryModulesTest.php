<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\Registry\Index;
use App\Models\Division;
use App\Models\RegistryRecord;
use App\Models\User;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistryModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);
    }

    private function user(string $role, string $division): User
    {
        $user = User::factory()->create([
            'is_default_password' => false, 'status' => 'active',
            'division_id' => Division::where('name', $division)->value('id'),
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function record(string $module, string $division, array $data, ?string $due = null): RegistryRecord
    {
        $cfg = config("registry.modules.$module");

        return RegistryRecord::create([
            'module' => $module, 'division_id' => Division::where('name', $division)->value('id'),
            'title' => $data[$cfg['title']] ?? null, 'due_date' => $due, 'data' => $data,
        ]);
    }

    public function test_every_role_in_the_matrix_can_open_pages_and_unknown_modules_404(): void
    {
        foreach ([RoleHelper::MANAGER, RoleHelper::PIC_PAINTING, RoleHelper::PIC_IRREG, RoleHelper::PIC_AIEC, RoleHelper::ADMIN_CGK] as $role) {
            $u = $this->user($role, 'Painting');
            foreach (array_keys(config('registry.modules')) as $key) {
                if ($key === 'training') {
                    $this->actingAs($u)->get("/registry/$key")->assertRedirect('/hr/training');

                    continue;
                }
                $this->actingAs($u)->get("/registry/$key")->assertOk();
            }
        }
        $this->actingAs($this->user(RoleHelper::MANAGER, 'Cabin'))->get('/registry/nope')->assertNotFound();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->actingAs($u)->get('/registry/pas')->assertForbidden();
    }

    public function test_pic_only_sees_and_writes_own_division_manager_sees_all(): void
    {
        $this->record('training', 'Painting', ['employee' => 'Kompresor Painting', 'training' => 'GSE', 'training_date' => '2026-10-01']);
        $this->record('training', 'Cabin', ['employee' => 'Stretcher Cabin', 'training' => 'First Aid', 'training_date' => '2026-10-01']);

        Livewire::actingAs($this->user(RoleHelper::PIC_PAINTING, 'Painting'))->test(Index::class, ['module' => 'training'])
            ->assertSee('Kompresor Painting')->assertDontSee('Stretcher Cabin');

        Livewire::actingAs($this->user(RoleHelper::MANAGER, 'Cabin'))->test(Index::class, ['module' => 'training'])
            ->assertSee('Kompresor Painting')->assertSee('Stretcher Cabin');

        $cabin = Division::where('name', 'Cabin')->value('id');
        Livewire::actingAs($this->user(RoleHelper::PIC_PAINTING, 'Painting'))->test(Index::class, ['module' => 'training'])
            ->call('create')
            ->set('division_id', $cabin)
            ->set('form.employee', 'Curang')->set('form.training', 'Tools')->set('form.training_date', '2026-10-01')
            ->call('save')->assertHasErrors('division_id');
        $this->assertDatabaseMissing('registry_records', ['title' => 'Curang']);
    }

    public function test_save_validates_and_stores_title_and_due_date(): void
    {
        $pic = $this->user(RoleHelper::PIC_AIEC, 'AIEC');

        Livewire::actingAs($pic)->test(Index::class, ['module' => 'pas'])
            ->call('create')
            ->call('save')->assertHasErrors(['form.holder', 'form.airport', 'form.valid_until'])
            ->set('form.holder', 'Andi')->set('form.airport', 'HLP')->set('form.codes', 'AD, P')->set('form.valid_until', now()->addDays(20)->toDateString())
            ->call('save')->assertHasNoErrors();

        $rec = RegistryRecord::where('module', 'pas')->firstOrFail();
        $this->assertSame('Andi', $rec->title);
        $this->assertSame(Division::where('name', 'AIEC')->value('id'), $rec->division_id);
        $this->assertSame('red', $rec->expiryStatus());
    }

    public function test_pas_expiry_markers(): void
    {
        $yellow = $this->record('pas', 'Cabin', ['holder' => 'A'], now()->addDays(50)->toDateString());
        $ok = $this->record('pas', 'Cabin', ['holder' => 'B'], now()->addMonths(6)->toDateString());

        $this->assertSame('yellow', $yellow->expiryStatus());
        $this->assertSame('ok', $ok->expiryStatus());
        $this->assertSame('none', $this->record('form', 'Cabin', ['form_name' => 'x'])->expiryStatus());
    }

    public function test_viewer_without_manage_cannot_write(): void
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'division_id' => Division::first()->id]);
        $u->givePermissionTo('registry.assess_hc.view');

        Livewire::actingAs($u)->test(Index::class, ['module' => 'assess_hc'])->call('create')->assertForbidden();
    }

    public function test_new_cleaning_pages_open(): void
    {
        $u = $this->user(RoleHelper::PIC_AIEC, 'AIEC');
        foreach (['gci', 'gce', 'lgt'] as $page) {
            $this->actingAs($u)->get("/modules/aircraft-cleaning/$page")->assertOk();
        }
    }

    public function test_dbi_page_opens_under_cabin_maintenance(): void
    {
        $u = $this->user(RoleHelper::PIC_CBM, 'Cabin');
        $this->actingAs($u)->get('/modules/dbi')->assertOk()->assertSee('Daily Beautification (DBI)');
    }

    public function test_dashboard_renders_with_new_sidebar(): void
    {
        $this->actingAs($this->user(RoleHelper::MANAGER, 'Cabin'))->get('/dashboard')
            ->assertOk()->assertSee('Development &amp; GA', false)->assertSee('Data PAS Bandara')->assertSee('Organisasi &amp; Assessment', false);
    }

    public function test_training_record_with_absence_reason_and_others_notes(): void
    {
        $cabin = Division::where('name', 'Cabin')->value('id');
        $manager = $this->user(RoleHelper::MANAGER, 'Cabin');

        // Test saving with absence reason Sakit
        Livewire::actingAs($manager)->test(Index::class, ['module' => 'training'])
            ->call('create')
            ->set('division_id', $cabin)
            ->set('form.employee', 'Budi Santoso')
            ->set('form.nik', '12345')
            ->set('form.training', 'Safety Awareness')
            ->set('form.training_date', '2026-10-15')
            ->set('form.attendance_status', 'Tidak Hadir')
            ->set('form.absence_reason', 'Sakit')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('registry_records', [
            'module' => 'training',
            'title' => 'Budi Santoso',
        ]);

        $rec = RegistryRecord::where('title', 'Budi Santoso')->firstOrFail();
        $this->assertSame('Tidak Hadir', $rec->data['attendance_status']);
        $this->assertSame('Sakit', $rec->data['absence_reason']);

        // Test saving with absence reason Others and notes
        Livewire::actingAs($manager)->test(Index::class, ['module' => 'training'])
            ->call('create')
            ->set('division_id', $cabin)
            ->set('form.employee', 'Sari Handayani')
            ->set('form.nik', '67890')
            ->set('form.training', 'First Aid Basic')
            ->set('form.training_date', '2026-10-16')
            ->set('form.attendance_status', 'Tidak Hadir')
            ->set('form.absence_reason', 'Others')
            ->set('form.absence_notes', 'Tugas darurat AOG di Surabaya')
            ->call('save')
            ->assertHasNoErrors();

        $recOthers = RegistryRecord::where('title', 'Sari Handayani')->firstOrFail();
        $this->assertSame('Others', $recOthers->data['absence_reason']);
        $this->assertSame('Tugas darurat AOG di Surabaya', $recOthers->data['absence_notes']);

        // Test rendering view contains badges and notes
        Livewire::actingAs($manager)->test(Index::class, ['module' => 'training'])
            ->assertSee('Budi Santoso')
            ->assertSee('Sakit')
            ->assertSee('Sari Handayani')
            ->assertSee('Others')
            ->assertSee('Tugas darurat AOG di Surabaya');
    }

    public function test_registry_export_and_import(): void
    {
        $cabin = Division::create(['name' => 'Cabin Maintenance', 'status' => 'Aktif'])->id;
        $manager = User::factory()->create(['division_id' => $cabin]);
        $manager->givePermissionTo(['registry.training.view', 'registry.training.manage']);

        RegistryRecord::create([
            'module' => 'training',
            'division_id' => $cabin,
            'title' => 'Agus Pratama',
            'data' => [
                'employee' => 'Agus Pratama',
                'training' => 'Fire Safety',
                'training_date' => '2026-10-15',
                'attendance_status' => 'Hadir',
            ],
        ]);

        Livewire::actingAs($manager)->test(Index::class, ['module' => 'training'])
            ->call('exportExcel')
            ->assertFileDownloaded();

        Livewire::actingAs($manager)->test(Index::class, ['module' => 'training'])
            ->call('openImportModal')
            ->assertSet('isImportModalOpen', true)
            ->call('closeImportModal')
            ->assertSet('isImportModalOpen', false);
    }
}
