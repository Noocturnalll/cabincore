<?php

/**
 * Daily briefing / attendant list / 5R evidence. Every station-shift is expected to submit three photos a day
 * (written to the "Entries" tab of the compliance spreadsheet by the Apps Script form).
 *
 * stations: code => ['shifts' => which shifts report, 'since' => first date it is expected (Y-m-d)]
 * Taken from what the sheet really contains (Apr-Oct 2026); edit when a station starts or stops a shift.
 */
return [
    'default_spreadsheet' => '1O2rUgKhS55oaSP1ryoXbGvU5gxv_UUJEeFCwlh9Bq7U',
    'tab' => 'Entries',

    'documents' => [
        'brf' => 'Briefing',
        'att' => 'Attendant List',
        '5r' => '5R',
    ],

    'stations' => [
        'CGK' => ['shifts' => ['Pagi', 'Malam'], 'since' => '2026-04-11'],
        'HLP' => ['shifts' => ['Pagi', 'Malam'], 'since' => '2026-04-11'],
        'SUB' => ['shifts' => ['Pagi', 'Malam'], 'since' => '2026-04-01'],
        'KNO' => ['shifts' => ['Pagi', 'Malam'], 'since' => '2026-04-01'],
        'UPG' => ['shifts' => ['Pagi', 'Malam'], 'since' => '2026-04-01'],
        'MDC' => ['shifts' => ['Pagi', 'Malam'], 'since' => '2026-04-11'],
        'BPN' => ['shifts' => ['Pagi', 'Malam'], 'since' => '2026-04-12'],
        'KOE' => ['shifts' => ['Malam'], 'since' => '2026-04-01'],
        'BTH' => ['shifts' => ['Malam'], 'since' => '2026-04-11'],
        'AMQ' => ['shifts' => ['Pagi'], 'since' => '2026-04-01'],
        'CBN' => ['shifts' => ['Pagi'], 'since' => '2026-04-01'],
    ],
];
