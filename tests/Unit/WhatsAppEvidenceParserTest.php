<?php

namespace Tests\Unit;

use App\Services\WhatsApp\WhatsAppEvidenceParser;
use PHPUnit\Framework\TestCase;

class WhatsAppEvidenceParserTest extends TestCase
{
    private WhatsAppEvidenceParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new WhatsAppEvidenceParser;
    }

    public function test_parses_standard_key_value_caption(): void
    {
        $caption = "T/C TYPE: RECTIFICATION\nDOC TYPE: NSRDI\nNO DOC: 90213";
        $result = $this->parser->parse($caption);

        $this->assertTrue($result['is_valid']);
        $this->assertSame('RECTIFICATION', $result['tc_type']);
        $this->assertSame('NSRDI', $result['doc_type']);
        $this->assertSame('90213', $result['doc_no']);
    }

    public function test_parses_comma_and_colon_shorthand(): void
    {
        $caption = 'TC: REPAIR, DOC: WO, NO: WO-88392';
        $result = $this->parser->parse($caption);

        $this->assertTrue($result['is_valid']);
        $this->assertSame('REPAIR', $result['tc_type']);
        $this->assertSame('WO', $result['doc_type']);
        $this->assertSame('WO-88392', $result['doc_no']);
    }

    public function test_parses_slash_separated_positional(): void
    {
        $caption = 'INSPECTION / CML / CML-2026-001';
        $result = $this->parser->parse($caption);

        $this->assertTrue($result['is_valid']);
        $this->assertSame('INSPECTION', $result['tc_type']);
        $this->assertSame('CML', $result['doc_type']);
        $this->assertSame('CML-2026-001', $result['doc_no']);
    }

    public function test_infers_doc_type_from_prefixed_number(): void
    {
        $caption = 'T/C TYPE: MODIFICATION, NO DOC: DMI-5521';
        $result = $this->parser->parse($caption);

        $this->assertTrue($result['is_valid']);
        $this->assertSame('MODIFICATION', $result['tc_type']);
        $this->assertSame('DMI', $result['doc_type']);
        $this->assertSame('DMI-5521', $result['doc_no']);
    }

    public function test_returns_invalid_when_doc_or_no_is_missing(): void
    {
        $result = $this->parser->parse('Halo selamat pagi teman-teman');
        $this->assertFalse($result['is_valid']);
        $this->assertNull($result['doc_type']);
        $this->assertNull($result['doc_no']);
    }
}
