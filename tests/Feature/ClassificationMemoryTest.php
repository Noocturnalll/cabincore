<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\DailyJobAssigment\Index as DjaPage;
use App\Models\DjaSyncReview;
use App\Models\User;
use App\Models\WoLog;
use App\Services\Dja\ClassificationMemory;
use App\Services\GoogleSheetsReader;
use App\Services\GoogleSheetsSyncService;
use Google\Service\Sheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassificationMemoryTest extends TestCase
{
    use RefreshDatabase;

    private const SHEET = '1MemorySheetAbCdEfGhIjKlMnOpQrStUvWx';

    private const HEADER = ['NO', 'WG', 'AC REG', 'TASK ID', 'CATEGORY', 'WO DESCRIPTION', 'TASK CARD', 'MAN HOUR', 'OPERATOR', 'TYPE', 'PLAN STA', 'REMARKS PPC TO LM', 'ACT STA', 'STATUS', 'CODE REASON', 'REASON OPEN'];

    private function row(string $task, string $desc, string $card): array
    {
        return ['1', 'WG 05', 'PK-GQA', $task, 'HT', $desc, $card, '2', 'ID', 'B737', 'CGK', '', '', '', '', ''];
    }

    private function sync(array $rows): array
    {
        $tabs = ['DJA' => array_merge([self::HEADER], $rows)];
        $reader = Mockery::mock(GoogleSheetsReader::class)->makePartial();
        $reader->shouldReceive('isReady')->andReturn(true);
        $reader->shouldReceive('service')->andReturn(Mockery::mock(Sheets::class));
        $reader->shouldReceive('tabTitles')->andReturn(array_keys($tabs));
        $reader->shouldReceive('values')->andReturnUsing(fn (string $id, string $tab) => $tabs[$tab] ?? []);
        $this->app->instance(GoogleSheetsReader::class, $reader);

        return app(GoogleSheetsSyncService::class)->syncDja(self::SHEET);
    }

    public function test_signature_ignores_suffixes_numbers_and_generic_cards(): void
    {
        $this->assertSame(['B789-33-010-00-01', 'card'], ClassificationMemory::signature('B789-33-010-00-01-IDN', null));
        $this->assertSame(['B789-33-010-00-01', 'card'], ClassificationMemory::signature('B789-33-010-00-01-GEF-IDN', null), 'a crew tag does not make it another job');
        $this->assertSame(['262400-RAI-12010-2', 'card'], ClassificationMemory::signature('262400-RAI-12010-2-IDN', 'x'));

        // generic line-check card: identified by its text, not by the shared 999999 number
        [$sig, $type] = ClassificationMemory::signature('999999-LCC-00000-3-WA-C-IDN', 'EMERGENCY LIGHTS OPERATIONAL CHECK OF EMERGENCY LIGHTS');
        $this->assertSame('text', $type);
        $this->assertSame('EMERGENCY LIGHTS OPERATIONAL CHECK EMERGENCY LIGHTS', $sig);

        // positions and serial numbers vary between jobs and must not split one job into many
        $this->assertSame(
            ClassificationMemory::signature(null, 'REPLACE SEAT BELT 12A SN 55')[0],
            ClassificationMemory::signature(null, 'REPLACE SEAT BELT 3F SN 99')[0]
        );
        $this->assertSame([null, null], ClassificationMemory::signature(null, 'OK'));
    }

    public function test_lookup_returns_the_remembered_answer_and_flags_disagreement(): void
    {
        $m = new ClassificationMemory;
        $this->assertNull($m->lookup('wo', 'B789-33-010-00-01-IDN', 'EMERGENCY LIGHTS CHECK'));

        $m->remember('wo', 'B789-33-010-00-01-IDN', 'EMERGENCY LIGHTS CHECK', 'LINE');
        $m->remember('wo', 'B789-33-010-00-01-IDN', 'EMERGENCY LIGHTS CHECK', 'LINE');
        $this->assertSame(['category' => 'LINE', 'hits' => 2, 'signature' => 'B789-33-010-00-01'], $m->lookup('wo', 'B789-33-010-00-01-GEF-IDN', 'something else'));
        $this->assertNull($m->lookup('dmi', 'B789-33-010-00-01-IDN', null), 'a WO decision is not a DMI decision');

        $m->remember('wo', 'B789-33-010-00-01-IDN', 'EMERGENCY LIGHTS CHECK', 'CBM');
        $this->assertSame('CONFLICT', $m->lookup('wo', 'B789-33-010-00-01-IDN', null)['category']);

        $this->assertFalse($m->remember('wo', null, 'OK', 'CBM'), 'nothing to recognise it by');
        $this->assertFalse($m->remember('nsrdi', 'X-1-2', 'A B C', 'CBM'), 'only WO and DMI are remembered');
    }

    public function test_a_review_decision_is_remembered_and_applied_to_the_next_sync(): void
    {
        // day 1: ATA 33 is accepted by the rules, so the job lands in the cabin WO log
        $this->sync([$this->row('W1', 'EMERGENCY LIGHTS OPERATIONAL CHECK OF EMERGENCY LIGHTS', 'B789-33-010-00-01-IDN')]);
        $this->assertSame(1, WoLog::where('wo_number', 'W1')->count());
        // the planner says it is line maintenance: remove it and teach the memory
        WoLog::where('wo_number', 'W1')->delete();

        $memory = new ClassificationMemory;
        $memory->remember('wo', 'B789-33-010-00-01-IDN', 'EMERGENCY LIGHTS OPERATIONAL CHECK OF EMERGENCY LIGHTS', 'LINE');

        // day 2: the same job comes back under another WO number, with the crew tag in the card
        $stats = $this->sync([$this->row('W2', 'EMERGENCY LIGHTS OPERATIONAL CHECK OF THE EMERGENCY LIGHTS.', 'B789-33-010-00-01-GEF-IDN')]);

        $this->assertSame(0, WoLog::where('wo_number', 'W2')->count(), 'remembered as line maintenance: not a cabin WO');
        $parked = DjaSyncReview::where('task_id', 'W2')->first();
        $this->assertNotNull($parked, 'it is parked in the audit list, never dropped silently');
        $this->assertSame('memory.line', $parked->rule);

        // and the other way around: a remembered CBM job is accepted even when no keyword or ATA matches
        $memory->remember('wo', 'A320-EA-OT-52-1290-IDN', 'GVI DOOR PROXIMITY', 'CBM');
        $this->sync([$this->row('W3', 'GVI AND ADJUSTMENT PROXIMITY', 'A320-EA-OT-52-1290-IDN')]);
        $this->assertSame(1, WoLog::where('wo_number', 'W3')->count());
    }

    public function test_accepting_a_review_row_teaches_the_memory(): void
    {
        Role::findOrCreate(RoleHelper::SUPER_ADMIN, 'web');
        $admin = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $admin->assignRole(RoleHelper::SUPER_ADMIN);

        // a doubtful row: only the weak word CLEANING
        $this->sync([$this->row('W9', 'AIR DATA CLEANING OF PITOT PROBES', 'A32-341300-11-1-02-IDN')]);
        $review = DjaSyncReview::where('task_id', 'W9')->first();
        $this->assertSame('review', $review->bucket);

        Livewire::actingAs($admin)->test(DjaPage::class)->call('acceptReview', $review->id);

        $hit = (new ClassificationMemory)->lookup('wo', 'A32-341300-11-1-02-IDN', null);
        $this->assertSame('CBM', $hit['category']);
    }
}
