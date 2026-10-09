<?php

namespace Tests\Feature;

use App\Services\Dja\DjaClassifier;
use App\Services\Dja\DjaRowMapper;
use Tests\TestCase;

class TaskCardAtaTest extends TestCase
{
    public function test_chapter_is_read_from_the_task_card_formats_used_in_the_dja(): void
    {
        foreach ([
            '262400-RAI-12010-2-IDN' => '26',
            '335121-RAI-10000-1-IDN' => '33',
            'A32-215200-02-2-02-IDN' => '21',
            'A32-341300-11-1-02-IDN' => '34',
            'B789-25-430-00-01-IDN' => '25',
            'GEN-EA-25-015-IDN' => '25',
            'A320-EA-32-1094-IDN' => '32',
            'A330-INT-12-668-IDN' => '12',
            'B737NG-EA-21-1811-IDN' => '21',
            'A320-EA-25-549' => '25',
        ] as $card => $ata) {
            $this->assertSame($ata, DjaClassifier::ataFromTaskCard($card), $card);
        }

        $this->assertNull(DjaClassifier::ataFromTaskCard('999999-LCC-00000-3-WA-C-IDN'), 'a line check card names no chapter');
        $this->assertNull(DjaClassifier::ataFromTaskCard(''));
        $this->assertNull(DjaClassifier::ataFromTaskCard(null));
    }

    public function test_wo_row_without_an_ata_column_takes_the_chapter_from_the_task_card(): void
    {
        $header = ['WG', 'AC REG', 'WO', 'WO CAT', 'WO DESCRIPTION', 'TASK CARD', 'TASK CARD DESCRIPTION', 'PLAN STA', 'STATUS WO'];
        $map = array_flip($header);
        $row = ['WG 05', 'PK-LAA', '2284299', 'HT', 'RESTORE THE ITEM', 'B789-25-430-00-01-IDN', 'MEDICAL KIT', 'CGK', 'OPEN'];

        $mapped = (new DjaRowMapper)->map('DJA', $row, $map);

        $this->assertSame('25', $mapped['ata']);
        $this->assertStringContainsString('MEDICAL KIT', $mapped['classify_text']);
        $this->assertSame('accept', (new DjaClassifier)->classify('wo', $mapped['ata'], null, $mapped['classify_text'])['action']);
    }

    public function test_generic_cleaning_words_alone_no_longer_auto_accept(): void
    {
        $c = new DjaClassifier;

        $this->assertSame('review', $c->classify('wo', null, null, 'AIR DATA CLEANING OF 2 OUT OF 3 PITOT PROBES')['action']);
        $this->assertSame('accept', $c->classify('wo', null, null, 'PORTABLE EXTINGUISHER REMOVAL OF HALON')['action']);
        $this->assertSame('accept', $c->classify('wo', null, null, 'CREW LIFEVEST POS FA1 EXPIRED')['action']);
        $this->assertSame('reject', $c->classify('wo', '32', null, 'CLEANING NLG AND MLG UPLOCK')['action']);
    }
}
