<?php

/**
 * Daily briefing / attendant list / 5R evidence. Every station-shift is expected to submit three photos a day.
 *
 * units: who reports.
 *   AIEC      expected shifts come from `stations` below; evidence is mirrored from the sheet (Apps Script form)
 *             and can also be uploaded in the app.
 *   CBM       Cabin Maintenance: a shift is expected where the roster has CBM people working that day.
 *   PAINTING  Aircraft Painting: the same, from the PAINTING team of the roster.
 *   EXTRA     Tambahan: extra evidence nobody expects every day (no target, only what was uploaded).
 * roles: which roles may upload for the unit (Manager, Super Admin and the other admins may upload for all).
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

    'units' => [
        'AIEC' => ['label' => 'AIEC', 'source' => 'config', 'teams' => ['AIEC'], 'roles' => ['PIC AIEC', 'Admin AIEC']],
        'CBM' => ['label' => 'Cabin Maintenance', 'source' => 'roster', 'teams' => ['CBM', 'IRREG'], 'roles' => ['PIC CBM', 'COD', 'Admin COD', 'Admin CGK']],
        'PAINTING' => ['label' => 'Aircraft Painting', 'source' => 'roster', 'teams' => ['PAINTING'], 'roles' => ['PIC Painting']],
        'EXTRA' => ['label' => 'Tambahan', 'source' => 'free', 'teams' => [], 'roles' => ['PIC Supporting', 'PIC Finishing']],
    ],

    /** roles that may upload for every unit */
    'upload_all_roles' => ['Super Admin', 'Manager', 'Deputy'],

    /** evidence files: stored on the public disk; images and PDF, in MB */
    'upload' => ['disk' => 'public', 'dir' => 'compliance', 'max_mb' => 8, 'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf']],

    /** shifts a roster-based or extra report can use */
    'shifts' => ['Pagi', 'Siang', 'Malam'],

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
