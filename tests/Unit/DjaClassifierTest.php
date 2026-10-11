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

    public function test_non_cabin_work_group_is_rejected(): void
    {
        $classifier = new DjaClassifier(config('dja'));
        $this->assertSame('reject', $classifier->classify('wo', null, null, 'REPLACE SEAT', 'AVIONICS')['action']);
        $this->assertSame('reject', $classifier->classify('wo', null, null, 'REPLACE SEAT', 'MECHANIC')['action']);
        $this->assertSame('reject', $classifier->classify('wo', null, null, 'REPLACE SEAT', 'STRUCTURE')['action']);
    }

    public function test_cockpit_and_ground_equipment_are_not_auto_accepted(): void
    {
        $classifier = new DjaClassifier(config('dja'));
        $this->assertSame('reject', $classifier->classify('wo', null, null, 'BOTH GROUND LOCK BAD CONDITION')['action']);
        $this->assertSame('reject', $classifier->classify('dmi', null, null, 'SLIDING TABLE HINGE MECHANISM CAPT SIDE BROKEN')['action']);
        $this->assertSame('reject', $classifier->classify('dmi', null, null, 'L/H FOOTREST F/O SIDE INOPERATIVE')['action']);
        $this->assertSame('review', $classifier->classify('nsrdi', null, 'CBM', 'HANDLE DOOR COCKPIT OUTSIDE DELEMINATED')['action']);
    }

    public function test_hard_non_cabin_jobs_rejected_regardless_of_ata(): void
    {
        $classifier = new DjaClassifier(config('dja'));

        // Cockpit windows from outside (even if ATA 56 or 25 is present)
        $this->assertSame('reject', $classifier->classify('wo', '56', null, 'DETAILED INSPECTION OF ALL COCKPIT WINDOWS INCLUDING SEALS FROM OUTSIDE A330')['action']);
        $this->assertSame('reject', $classifier->classify('wo', null, null, 'B737NG FLIGHT COMPARTMENT WINDOW 1 HUMP SEAL REPAIR')['action']);

        // Cargo compartments (even if ATA 25 is present)
        $this->assertSame('reject', $classifier->classify('wo', '25', null, 'INSPECT AFT CARGO COMPARTMENT')['action']);
        $this->assertSame('reject', $classifier->classify('wo', null, null, 'INSPECT (GENERAL VISUAL) THE FWD CARGO COMPARTMENT FLOOR, CEILING, SIDEWALL')['action']);
        $this->assertSame('reject', $classifier->classify('nsrdi', null, 'CBM', 'FWD CARGO NET BAD CONDITION')['action']);

        // Systems & airframe
        $this->assertSame('reject', $classifier->classify('wo', null, null, 'GENERAL VISUAL INSPECTION PACK AREA (CLAMP, SEAL AND GASKET)')['action']);
        $this->assertSame('reject', $classifier->classify('wo', null, null, 'MAIN BATTERY')['action']);
        $this->assertSame('reject', $classifier->classify('wo', null, null, 'BATTERY AND CHARGER SYSTEM')['action']);
        $this->assertSame('reject', $classifier->classify('wo', null, null, 'INSPECTION OF MAIN LANDING GEAR SUPPORT RIB5 LUGS')['action']);

        // Exterior cleaning & mechanical waste / crew oxygen systems
        $this->assertSame('reject', $classifier->classify('wo', null, 'CBMS', 'DEEP CLEANING EXTERIOR')['action']);
        $this->assertSame('reject', $classifier->classify('wo', null, 'OP', 'VACUUM TOILET SYSTEM INSPECT WATER SEPARATOR AND FILTER')['action']);
        $this->assertSame('reject', $classifier->classify('wo', null, 'DEFER', 'VACUUM TOILET SYSTEM DETAILED INSPECTION OF CLEANLINESS INSIDE WASTE TANK')['action']);
        $this->assertSame('reject', $classifier->classify('wo', null, 'HT', 'RESTORE THE FLIGHT CREW OXYGEN MASK/REGULATOR')['action']);
        $this->assertSame('reject', $classifier->classify('wo', null, 'HT', 'DETAILED VISUAL INSPECTION OF CREW OXYGEN MASK AND STOWAGE BOX')['action']);
    }

    public function test_cockpit_emergency_equipment_accepted(): void
    {
        $classifier = new DjaClassifier(config('dja'));

        $this->assertSame('accept', $classifier->classify('wo', null, null, 'REPLACEMENT CREW LIFEVEST 4EA (COCKPIT, FA2, FA5, FA6)')['action']);
        $this->assertSame('accept', $classifier->classify('wo', null, null, 'REMOVAL OF COCKPIT FLASHLIGHTS FOR BATTERY DISCARD AND SUBSEQUENT OPERATIONAL TEST')['action']);
        $this->assertSame('accept', $classifier->classify('wo', null, null, 'REPLACEMENT LIFE VEST CREW AT COCKPIT SEAT PILOT')['action']);
    }
}
