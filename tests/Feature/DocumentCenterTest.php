<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Documents\Center;
use App\Livewire\Documents\Master;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentCenterTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_master_page_is_super_admin_only_but_center_is_open_to_all(): void
    {
        $pic = $this->userWithRole(RoleHelper::PIC_CABIN);
        $admin = $this->userWithRole(RoleHelper::SUPER_ADMIN);

        $this->actingAs($pic)->get('/documents')->assertOk();
        $this->actingAs($pic)->get('/documents/master')->assertForbidden();
        $this->actingAs($admin)->get('/documents/master')->assertOk();

        Livewire::actingAs($pic)->test(Master::class)->assertForbidden();
    }

    public function test_upload_accepts_pdf_and_rejects_executable_or_html_files(): void
    {
        $admin = $this->userWithRole(RoleHelper::SUPER_ADMIN);

        $component = Livewire::actingAs($admin)->test(Master::class)
            ->call('create')->set('title', 'SOP Kabin')->set('category', 'sop');

        foreach (['shell.php', 'page.html', 'icon.svg'] as $bad) {
            $component->set('file', UploadedFile::fake()->create($bad, 10))
                ->call('save')->assertHasErrors('file');
        }
        $this->assertSame(0, Document::count());

        $component->set('file', UploadedFile::fake()->create('sop.pdf', 100, 'application/pdf'))
            ->call('save')->assertHasNoErrors();

        $doc = Document::firstOrFail();
        $this->assertSame('SOP Kabin', $doc->title);
        $this->assertSame('pdf', $doc->file_extension);
        Storage::disk('public')->assertExists($doc->file_path);
    }

    public function test_unknown_category_is_rejected(): void
    {
        $admin = $this->userWithRole(RoleHelper::SUPER_ADMIN);

        Livewire::actingAs($admin)->test(Master::class)
            ->call('create')->set('title', 'X')->set('category', 'rahasia')
            ->set('file', UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))
            ->call('save')->assertHasErrors('category');
    }

    public function test_replacing_the_file_removes_the_old_one_and_delete_removes_everything(): void
    {
        $admin = $this->userWithRole(RoleHelper::SUPER_ADMIN);
        $component = Livewire::actingAs($admin)->test(Master::class)
            ->call('create')->set('title', 'Template')->set('category', 'template')
            ->set('file', UploadedFile::fake()->create('a.xlsx', 20))->call('save')->assertHasNoErrors();

        $doc = Document::firstOrFail();
        $oldPath = $doc->file_path;

        $component->call('edit', $doc->id)->set('file', UploadedFile::fake()->create('b.xlsx', 30))->call('save')->assertHasNoErrors();
        $doc->refresh();
        $this->assertNotSame($oldPath, $doc->file_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($doc->file_path);

        // Editing only the title keeps the file
        $kept = $doc->file_path;
        $component->call('edit', $doc->id)->set('title', 'Template v2')->call('save')->assertHasNoErrors();
        $this->assertSame($kept, $doc->fresh()->file_path);

        $component->call('delete', $doc->id);
        $this->assertSame(0, Document::count());
        Storage::disk('public')->assertMissing($kept);
    }

    public function test_download_uses_a_readable_name_and_404s_when_the_file_is_gone(): void
    {
        $pic = $this->userWithRole(RoleHelper::PIC_CABIN);
        Storage::disk('public')->put('documents/abc123.pdf', 'pdf-bytes');
        $doc = Document::create(['title' => 'SOP Cleaning Kabin', 'category' => 'sop', 'file_path' => 'documents/abc123.pdf', 'file_size' => '1 KB', 'file_extension' => 'pdf']);

        $this->actingAs($pic)->get(route('documents.download', $doc))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=sop-cleaning-kabin.pdf');

        Storage::disk('public')->delete('documents/abc123.pdf');
        $this->actingAs($pic)->get(route('documents.download', $doc))->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->get(route('documents.download', $doc))->assertRedirect(route('login'));
    }

    public function test_center_filters_by_category_and_search(): void
    {
        $pic = $this->userWithRole(RoleHelper::PIC_CABIN);
        Document::create(['title' => 'Template Harian', 'category' => 'template', 'file_path' => 'documents/a.xlsx', 'file_extension' => 'xlsx']);
        Document::create(['title' => 'Regulasi CASR', 'category' => 'regulasi', 'file_path' => 'documents/b.pdf', 'file_extension' => 'pdf']);
        Document::create(['title' => 'Panduan Lain', 'category' => 'lainnya', 'file_path' => 'documents/c.docx', 'file_extension' => 'docx']);

        $c = Livewire::actingAs($pic)->test(Center::class);
        $this->assertSame(3, $c->viewData('documents')->total());

        $c->call('setCategory', 'regulasi');
        $this->assertSame(['Regulasi CASR'], $c->viewData('documents')->pluck('title')->all());

        $c->call('setCategory', 'bogus');
        $this->assertSame(3, $c->viewData('documents')->total());

        $c->set('search', 'panduan');
        $this->assertSame(['Panduan Lain'], $c->viewData('documents')->pluck('title')->all());
    }
}
