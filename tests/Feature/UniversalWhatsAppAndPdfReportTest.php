<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Components\WhatsAppReportModal;
use App\Models\User;
use App\Services\WhatsApp\UniversalWhatsAppReportService;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UniversalWhatsAppAndPdfReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);

        $this->admin = User::factory()->create([
            'status' => 'active',
            'is_default_password' => false,
        ]);
        $this->admin->assignRole(RoleHelper::SUPER_ADMIN);
    }

    public function test_universal_report_service_generates_text_for_all_modules(): void
    {
        $service = app(UniversalWhatsAppReportService::class);

        // 1. Executive
        $exec = $service->generateReport('executive', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);
        $this->assertEquals('executive', $exec['module']);
        $this->assertStringContainsString('LION GROUP AIRCRAFT CABIN TECHNICIAN', $exec['report_text']);
        $this->assertStringContainsString('CABIN MAINTENANCE', $exec['report_text']);
        $this->assertStringContainsString('AIRCRAFT PAINTING', $exec['report_text']);
        $this->assertStringContainsString('AIRCRAFT CLEANING', $exec['report_text']);
        $this->assertStringNotContainsStringIgnoringCase('engineer', $exec['report_text']);

        // 2. CBM / COD
        $cbm = $service->generateReport('cbm', [
            'start_date' => now()->toDateString(),
        ]);
        $this->assertEquals('cbm', $cbm['module']);
        $this->assertStringContainsString('CABIN ON-DUTY', $cbm['report_text']);
        $this->assertStringNotContainsStringIgnoringCase('engineer', $cbm['report_text']);

        // 3. Painting
        $painting = $service->generateReport('painting', [
            'start_date' => now()->toDateString(),
        ]);
        $this->assertEquals('painting', $painting['module']);
        $this->assertStringContainsString('AIRCRAFT PAINTING PRODUCTION', $painting['report_text']);
        $this->assertStringNotContainsStringIgnoringCase('engineer', $painting['report_text']);

        // 4. Cleaning
        $cleaning = $service->generateReport('cleaning', [
            'start_date' => now()->toDateString(),
        ]);
        $this->assertEquals('cleaning', $cleaning['module']);
        $this->assertStringContainsString('AIRCRAFT CLEANING (AIEC)', $cleaning['report_text']);
        $this->assertStringNotContainsStringIgnoringCase('engineer', $cleaning['report_text']);

        // 5. CML
        $cml = $service->generateReport('cml', [
            'start_date' => now()->toDateString(),
        ]);
        $this->assertEquals('cml', $cml['module']);
        $this->assertStringContainsString('REKAPITULASI DEFECT CML', $cml['report_text']);
        $this->assertStringNotContainsStringIgnoringCase('engineer', $cml['report_text']);
    }

    public function test_executive_pdf_report_renders_successfully_without_engineer_word(): void
    {
        $response = $this->actingAs($this->admin)->get('/reports/executive/pdf?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString());

        $response->assertOk();
        $response->assertSee('LION GROUP AIRCRAFT CABIN TECHNICIAN');
        $response->assertSee('PRODUCTION SUMMARY &amp; DAILY EXECUTIVE REPORT', false);
        $response->assertSee('Matriks Hasil Produksi per Stasiun Kerja');

        $content = $response->getContent();
        $this->assertStringNotContainsStringIgnoringCase('engineer', $content);
    }

    public function test_whats_app_report_modal_component_opens_and_sets_content(): void
    {
        Livewire::actingAs($this->admin)
            ->test(WhatsAppReportModal::class)
            ->assertSet('isOpen', false)
            ->dispatch('open-wa-report-modal',
                module: 'executive',
                startDate: now()->toDateString(),
                endDate: now()->toDateString(),
                station: 'CGK'
            )
            ->assertSet('isOpen', true)
            ->assertSet('module', 'executive')
            ->assertSet('station', 'CGK')
            ->assertSet('reportTitle', 'Laporan Eksekutif Produksi 3 Pilar')
            ->assertSee('Preview Format Pesan WhatsApp:');
    }
}
