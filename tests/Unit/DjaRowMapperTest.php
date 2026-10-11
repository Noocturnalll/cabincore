<?php

namespace Tests\Unit;

use App\Models\DailyJobAssignment;
use App\Models\WoLog;
use App\Services\Dja\DjaRowMapper;
use App\Services\GoogleSheetsReader;
use Tests\TestCase;

class DjaRowMapperTest extends TestCase
{
    public function test_kind_for_tab_handles_exact_and_fuzzy_variations(): void
    {
        $this->assertSame('wo', DjaRowMapper::kindForTab('DJA'));
        $this->assertSame('wo', DjaRowMapper::kindForTab('DJA R01'));
        $this->assertSame('wo', DjaRowMapper::kindForTab('DJA R01/WO'));
        $this->assertSame('wo', DjaRowMapper::kindForTab('DJA WO'));
        $this->assertSame('wo', DjaRowMapper::kindForTab('Sheet1'));
        $this->assertSame('dmi', DjaRowMapper::kindForTab('DJA DMI'));
        $this->assertSame('dmi', DjaRowMapper::kindForTab('DMI'));
        $this->assertSame('nsrdi', DjaRowMapper::kindForTab('DJA NSRD'));
        $this->assertSame('nsrdi', DjaRowMapper::kindForTab('DJA NSRDI'));
        $this->assertSame('nsrdi', DjaRowMapper::kindForTab('DJA AOC NSRDIL R01'));
        $this->assertSame('nsrdi', DjaRowMapper::kindForTab('DJA NSRDIL'));
        $this->assertSame('nsrdi', DjaRowMapper::kindForTab('AOC NSRDI'));
    }

    public function test_mapper_reads_plan_date_as_date_and_never_uses_refresh_date_as_date(): void
    {
        $mapper = new DjaRowMapper;
        $header = ['WG', 'AC REG', 'WO', 'WO CAT', 'DESCRIPTION', 'PLAN STA', 'STATUS', 'PLAN DATE', 'REFRESH DATE', 'CODE REASON', 'REASON'];
        $headerMap = array_flip($header);

        $row = [
            'CABIN', 'PK-LGF', 'WO-12345', 'CAT A', 'REPAIR SEAT 12A', 'CGK', 'OPEN',
            '10/10/2026', '11/10/2026', 'NS', 'WAITING SPAREPART SEAT COVER',
        ];

        $mapped = $mapper->map('DJA R01', $row, $headerMap);

        $this->assertNotNull($mapped);
        $this->assertSame('2026-10-10', $mapped['date'], 'Date must strictly use plan_date, not refresh_date');
        $this->assertSame('2026-10-10', $mapped['plan_date']);
        $this->assertSame('2026-10-11', $mapped['refresh_date']);
        $this->assertSame('NS', $mapped['log']['code_open']);
        $this->assertSame('WAITING SPAREPART SEAT COVER', $mapped['log']['reason_open']);
        $this->assertSame('NS', $mapped['log']['hold_reason_category']);
        $this->assertSame('WAITING SPAREPART SEAT COVER', $mapped['log']['hold_remarks']);
    }

    public function test_mapper_reads_code_and_reason_for_dmi_and_nsrdi(): void
    {
        $mapper = new DjaRowMapper;

        // DMI
        $dmiHeader = ['REG', 'DESCRIPTION', 'PN', 'DMI NO', 'DMI CAT', 'PLAN STA', 'CAT', 'STATUS', 'REMARK', 'CODE REASON', 'REASON OPEN', 'PLAN DATE'];
        $dmiRow = ['PK-LGF', 'CARPET TORN', 'PN-99', 'DMI-100', 'CAT C', 'CGK', 'C', 'OPEN', 'INITIAL REMARK', 'MP', 'MANPOWER SHORTAGE', '10/10/2026'];
        $dmiMapped = $mapper->map('DJA DMI', $dmiRow, array_flip($dmiHeader));

        $this->assertNotNull($dmiMapped);
        $this->assertSame('2026-10-10', $dmiMapped['date']);
        $this->assertSame('MP', $dmiMapped['log']['hold_reason_category']);
        $this->assertSame('MANPOWER SHORTAGE', $dmiMapped['log']['hold_remarks']);

        // NSRDI
        $nsrdiHeader = ['WG', 'AC REG', 'NSRDI NO', 'DESCRIPTION', 'CAT', 'REPORT DATE', 'DUE DATE', 'PN', 'PART DESC', 'DEFER', 'AOC', 'TYPE', 'PLAN STA', 'PLAN DATE', 'REMARKS', 'STATUS', 'CLOSE DATE', 'CODE REASON', 'REASON OPEN'];
        $nsrdiRow = ['CABIN', 'PK-LGF', 'NSRDI-555', 'TABLE BROKEN', 'DEFECT', '01/10/2026', '15/10/2026', 'PN-11', 'TABLE', 'NO', 'AOC', '738', 'CGK', '10/10/2026', 'REMARK', 'OPEN', null, 'WT', 'WAITING TOOL'];
        $nsrdiMapped = $mapper->map('DJA AOC NSRDIL R01', $nsrdiRow, array_flip($nsrdiHeader));

        $this->assertNotNull($nsrdiMapped);
        $this->assertSame('2026-10-10', $nsrdiMapped['date']);
        $this->assertSame('2026-10-10', $nsrdiMapped['log']['plan_date']);
        $this->assertSame('WT', $nsrdiMapped['log']['code_open']);
        $this->assertSame('WAITING TOOL', $nsrdiMapped['log']['reason_open']);
        $this->assertSame('WT', $nsrdiMapped['log']['hold_reason_category']);
        $this->assertSame('WAITING TOOL', $nsrdiMapped['log']['hold_remarks']);
    }

    public function test_google_sheets_reader_resolve_tabs_fuzzy(): void
    {
        $reader = new GoogleSheetsReader;
        $wanted = ['DJA', 'DJA DMI', 'DJA NSRD'];
        $actual = ['List AC', 'DJA R01', 'DJA DMI', 'DJA AOC NSRDIL R01'];

        $resolved = $reader->resolveTabs($wanted, $actual);

        $this->assertSame('DJA R01', $resolved['DJA']);
        $this->assertSame('DJA DMI', $resolved['DJA DMI']);
        $this->assertSame('DJA AOC NSRDIL R01', $resolved['DJA NSRD']);
    }

    public function test_mapper_extracts_descriptions_from_various_header_aliases_and_fallbacks(): void
    {
        $mapper = new DjaRowMapper;

        // Header with DMI DESCRIPTION
        $dmiHeader = ['REG', 'DMI DESCRIPTION', 'DMI NO', 'PLAN DATE', 'STATUS'];
        $dmiRow = ['PK-LGF', 'TRAY TABLE STUCK AT 14B', 'DMI-888', '10/10/2026', 'OPEN'];
        $dmiMapped = $mapper->map('DJA DMI', $dmiRow, array_flip($dmiHeader));

        $this->assertNotNull($dmiMapped);
        $this->assertSame('TRAY TABLE STUCK AT 14B', $dmiMapped['description']);

        // NSRDI with PART DESC fallback when description column is empty
        $nsrdiHeader = ['REG', 'NSRDI NO', 'PART DESC', 'PLAN DATE', 'STATUS'];
        $nsrdiRow = ['PK-LGF', 'NSR-111', 'ARMREST CAP REPLACEMENT', '10/10/2026', 'OPEN'];
        $nsrdiMapped = $mapper->map('DJA AOC NSRDIL R01', $nsrdiRow, array_flip($nsrdiHeader));

        $this->assertNotNull($nsrdiMapped);
        $this->assertSame('ARMREST CAP REPLACEMENT', $nsrdiMapped['description']);
    }

    public function test_daily_job_assignment_description_fallback_to_related_logs(): void
    {
        $dja = new DailyJobAssignment([
            'aircraft_registration' => 'PK-LGF',
            'job_type' => 'DMI',
            'station' => 'CGK',
            'date' => '2026-10-10',
        ]);

        // When description attribute is null and no relations loaded
        $this->assertNull($dja->description);

        // When woLogs is loaded with description
        $woLog = new WoLog(['description' => 'REPLACE LAVATORY FAUCET']);
        $dja->setRelation('woLogs', collect([$woLog]));
        $dja->setRelation('dmiLogs', collect());
        $dja->setRelation('nsrdiLogs', collect());
        $dja->setRelation('cmlLogs', collect());

        $this->assertSame('REPLACE LAVATORY FAUCET', $dja->description);

        // When dja has its own description attribute, it takes precedence
        $dja->description = 'EXPLICIT DJA DESCRIPTION';
        $this->assertSame('EXPLICIT DJA DESCRIPTION', $dja->description);
    }
}
