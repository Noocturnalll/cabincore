<?php

namespace Tests\Unit;

use App\Services\Dja\DjaClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DjaClassifierTest extends TestCase
{
    private function action(string $kind, ?string $ata, ?string $category, ?string $description): string
    {
        return (new DjaClassifier(config('dja')))->classify($kind, $ata, $category, $description)['action'];
    }

    public static function cabinCases(): array
    {
        return [
            // [kind, ata, description, expected, why]
            'ATA 72 is rejected even with a cabin keyword' => ['wo', '72-00', 'REPLACE SEAT COVER', 'reject'],
            'ATA 32 is rejected' => ['dmi', '32-10', 'SEAT BELT LOOSE', 'reject'],
            'accepted ATA needs no keyword' => ['wo', '25-21', 'CHECK SOMETHING ELSE', 'accept'],
            'ATA is read from the first two digits' => ['wo', ' 35.10', 'ANYTHING', 'accept'],
            'keyword with punctuation' => ['wo', null, 'Replace life-vest L/H', 'accept'],
            'keyword is case-insensitive' => ['wo', null, 'check Oxygen Mask', 'accept'],
            'plural of a keyword' => ['wo', null, 'Replace SEATS row 12', 'accept'],
            'keyword must be a whole word' => ['wo', null, 'Meeting in SEATTLE', 'reject'],
            'LAV does not match LAVA' => ['dmi', null, 'LAVA SENSOR FAULT', 'reject'],
            'LAV matches as a word' => ['dmi', null, 'LAV door handle broken', 'accept'],
            'DMI keyword' => ['dmi', null, 'NO SMOKING SIGN NOT ILL', 'accept'],
            'WO keyword is not a DMI keyword' => ['dmi', null, 'GALLEY ROLLER BLIND', 'reject'],
            'cabin keyword with a non-cabin word is a conflict' => ['wo', null, 'SEAT BRACKET NEAR ENGINE MOUNT', 'review'],
            'weak word only goes to review' => ['wo', null, 'CABIN CARPET WORN OUT', 'review'],
            'nothing matches' => ['wo', null, 'REPLACE FUEL BOOST PUMP', 'reject'],
            'empty description' => ['wo', null, '', 'reject'],
        ];
    }

    #[DataProvider('cabinCases')]
    public function test_wo_and_dmi_rows(string $kind, ?string $ata, string $description, string $expected): void
    {
        $this->assertSame($expected, $this->action($kind, $ata, null, $description));
    }

    public function test_nsrdi_is_decided_by_category_only(): void
    {
        $this->assertSame('accept', $this->action('nsrdi', null, 'CBM', 'anything'));
        $this->assertSame('accept', $this->action('nsrdi', null, ' painting ', 'anything'));
        $this->assertSame('reject', $this->action('nsrdi', null, 'AVIONICS', 'SEAT BELT'));
        $this->assertSame('review', $this->action('nsrdi', null, '', 'SEAT BELT'));
    }

    public function test_the_verdict_explains_itself(): void
    {
        $verdict = (new DjaClassifier(config('dja')))->classify('wo', null, null, 'REPLACE LIFE VEST');

        $this->assertSame('keyword.include', $verdict['rule']);
        $this->assertStringContainsString('LIFE VEST', $verdict['reason']);
    }
}
