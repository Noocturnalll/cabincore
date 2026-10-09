<?php

use App\Services\Compliance\ComplianceSyncService;
use App\Services\Sources\AmmSync;
use App\Services\Sources\CbmClosingSync;
use App\Services\Sources\TargetSync;

/**
 * Data sources: the Google Sheets the system reads.
 *
 *  planner    produced by the planner (DJA, AC Movement). Read only; each has its own page and link.
 *  result     the daily results the teams keep (closings with crew, briefing evidence, ...). Synced here.
 *  reference  slow-changing lists (aircraft master, working groups, targets).
 *
 * The spreadsheet id can be replaced on the Sumber Data page; `default` is used until then.
 */
return [
    'sources' => [
        'dja' => [
            'kind' => 'planner', 'label' => 'DJA (Daily Job Assignment)',
            'about' => 'Dikirim planner tiap hari dengan link baru. Tempel link-nya di halaman DJA lalu sinkronkan.',
            'route' => 'modules.dja',
        ],
        'ac_movement' => [
            'kind' => 'planner', 'label' => 'AC Movement',
            'about' => 'Dari planner, ikut berubah mengikuti sheet (tiap 5 menit).',
            'route' => 'modules.ac-movement',
        ],
        'compliance' => [
            'kind' => 'result', 'label' => 'Briefing, Attendant List & 5R',
            'about' => 'Foto bukti harian per station dan shift (tab Entries). Sinkron tiap jam.',
            'default' => '1O2rUgKhS55oaSP1ryoXbGvU5gxv_UUJEeFCwlh9Bq7U', 'tab' => 'Entries', 'every' => 'hourly',
            'handler' => ComplianceSyncService::class,
        ],
        'cbm_closing' => [
            'kind' => 'result', 'label' => 'Closing CBM (kru, jam mulai-selesai)',
            'about' => 'Tab DATA workbook CBM: dokumen yang dikerjakan beserta ID kru dan jam. Dibaca seperti Laporan Leader: dicocokkan dengan DJA, sisanya unplanned.',
            'default' => '1rbfbWEjkTW5VFeybGmin-MNbCNZJcEX9Z377aFoUCPk', 'tab' => 'DATA', 'every' => 'hourly',
            'handler' => CbmClosingSync::class,
        ],
        'amm' => [
            'kind' => 'reference', 'label' => 'AMM CBM (Working Group pesawat)',
            'about' => 'Tab WORKING GROUP: pesawat milik WG berapa. Mengisi kolom WG di master pesawat.',
            'default' => '1jJ9KXttfwvfZnQKqXfa7dYeC8KgwDOdJA9XnpqFklAw', 'tab' => 'WORKING GROUP', 'every' => 'daily',
            'handler' => AmmSync::class,
        ],
        'targets' => [
            'kind' => 'reference', 'label' => 'Target closed per hari (resume NSRDI)',
            'about' => 'Tab TARGET workbook resume: target per station dan AOC. Mengisi Master Sistem › Target Closed per Hari.',
            'default' => '1iH02K8QcKQm_1mdRQzcwbnj8A1tSUwp6', 'tab' => 'TARGET', 'every' => 'daily',
            'handler' => TargetSync::class,
        ],
    ],
];
