<?php

namespace Tests\Feature;

use App\Livewire\Modules\Manual\Index;
use App\Models\ManualReference;
use App\Models\User;
use App\Services\GoogleSheetsReader;
use App\Services\Sources\ManualReferenceSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ManualReferenceTest extends TestCase
{
    use RefreshDatabase;

    private const DRIVE = 'https://drive.google.com/file/d/ABC123_x-y/view?usp=drive_link';

    private function grid(): array
    {
        return [
            [['- AMM ATA CHAPTER BOEING B737NG', null]],
            [],
            [['ATA CHAPTER', null], ['DESCRIPTION', null], ['LINK', null]],
            [['11-00-00', null], ['PLACARD AND MARKING', null], ['', null]],
            [['11-21-00', null], ['FUSELAGE', null], ['CHAPTER 11', self::DRIVE]],
            [['', null], ['', null], ['', null]],
        ];
    }

    public function test_the_header_row_is_found_and_links_keep_their_hyperlink(): void
    {
        $rows = ManualReferenceSync::parse($this->grid());

        $this->assertCount(2, $rows);
        $this->assertSame(['ATA CHAPTER', 'DESCRIPTION', 'LINK'], array_keys($rows[0]['cells']));
        $this->assertSame(['CHAPTER 11', self::DRIVE], $rows[1]['links']['LINK']);
        $this->assertSame([], $rows[0]['links']);
        $this->assertStringContainsString('fuselage', $rows[1]['search']);
    }

    public function test_a_reordered_tab_still_reads_by_header_name(): void
    {
        $grid = [
            [['LINK', null], ['DESCRIPTION', null], ['ATA CHAPTER', null]],
            [['CH 5', self::DRIVE], ['FUSELAGE', null], ['11-21-00', null]],
        ];

        $row = ManualReferenceSync::parse($grid)[0];

        $this->assertSame('11-21-00', $row['cells']['ATA CHAPTER']);
        $this->assertSame(self::DRIVE, $row['links']['LINK'][1]);
    }

    public function test_sync_replaces_a_tab_but_never_wipes_it_with_an_empty_read(): void
    {
        $reader = Mockery::mock(GoogleSheetsReader::class);
        $reader->shouldReceive('isReady')->andReturn(true);
        $reader->shouldReceive('tabTitles')->andReturn(['COVER', 'AMM B737', 'EMPTY TAB']);

        $sync = new class($reader, $this->grid()) extends ManualReferenceSync
        {
            public function __construct($reader, private array $data)
            {
                parent::__construct($reader);
            }

            protected function grid(string $spreadsheetId, string $tab): array
            {
                return $tab === 'AMM B737' ? $this->data : [];
            }
        };

        $result = $sync->sync('sheet-id');

        $this->assertTrue($result['ok']);
        $this->assertSame(2, ManualReference::where('tab', 'AMM B737')->count());
        $this->assertSame(0, ManualReference::where('tab', 'COVER')->count(), 'COVER is skipped');

        // second run replaces, does not duplicate
        $sync->sync('sheet-id');
        $this->assertSame(2, ManualReference::where('tab', 'AMM B737')->count());
    }

    public function test_only_drive_file_links_get_a_preview(): void
    {
        $this->assertSame('https://drive.google.com/file/d/ABC123_x-y/preview', ManualReference::previewUrl(self::DRIVE));
        $this->assertNull(ManualReference::previewUrl('https://evil.example.com/file/d/ABC/view'));
        $this->assertNull(ManualReference::previewUrl('javascript:alert(1)'));
    }

    public function test_the_page_lists_searches_and_opens_links_in_a_new_tab(): void
    {
        foreach (ManualReferenceSync::parse($this->grid()) as $r) {
            ManualReference::create(['tab' => 'AMM B737', 'row_no' => $r['row_no'], 'cells' => $r['cells'], 'links' => $r['links'], 'search' => $r['search']]);
        }
        $this->actingAs(User::factory()->create(['status' => 'active']));

        Livewire::test(Index::class)
            ->assertSee('FUSELAGE')
            ->assertSee('PLACARD AND MARKING')
            ->assertSee('target="_blank"', false)
            ->assertSee('noopener', false)
            ->set('search', 'fuselage')
            ->assertSee('FUSELAGE')
            ->assertDontSee('PLACARD AND MARKING');
    }
}
