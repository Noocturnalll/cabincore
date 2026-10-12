<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\LgtRecord;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LgtExport implements Export, WithMultipleSheets
{
    protected string $date;

    protected string $station;

    protected string $statusFilter;

    protected ?array $allowedStations;

    public function __construct(
        string $date = '',
        string $station = '',
        string $statusFilter = '',
        ?array $allowedStations = null
    ) {
        $this->date = $date;
        $this->station = $station;
        $this->statusFilter = $statusFilter;
        $this->allowedStations = $allowedStations;
    }

    public function sheets(): array
    {
        $query = LgtRecord::query();

        if ($this->date !== '') {
            $query->whereDate('work_date', $this->date);
        }

        if ($this->allowedStations !== null) {
            $query->whereIn('station', $this->allowedStations);
        }

        if ($this->station !== '') {
            $query->where('station', $this->station);
        }

        if ($this->statusFilter !== '') {
            $query->where(fn ($w) => $w->where('cbm_status', $this->statusFilter)->orWhere('aiec_status', $this->statusFilter));
        }

        $records = $query->orderBy('work_date', 'desc')->orderBy('sta_time')->get();

        $mapped = $records->map(function (LgtRecord $r) {
            return [
                'work_date' => $r->work_date ? $r->work_date->format('Y-m-d') : '-',
                'station' => $r->station ?: '-',
                'aircraft_registration' => $r->aircraft_registration ?: '-',
                'aoc' => $r->aoc ?: '-',
                'sta_time' => $r->sta_time ?: '-',
                'std_time' => $r->std_time ?: '-',
                'cbm_action' => $r->cbm_action ?: '-',
                'cbm_mp' => $r->cbm_mp ?: '-',
                'cbm_status' => strtoupper($r->cbm_status ?: 'OPEN'),
                'aiec_action' => $r->aiec_action ?: '-',
                'aiec_mp' => $r->aiec_mp ?: '-',
                'aiec_status' => strtoupper($r->aiec_status ?: 'OPEN'),
                'reason' => $r->reason ?: '-',
            ];
        });

        // 1. Pivot Table: Station vs CBM Status
        $matrixStationCbm = PivotBuilder::matrix(
            $mapped,
            'station',
            'cbm_status',
            null,
            'count'
        );

        // 2. Pivot Table: Station vs AIEC Status
        $matrixStationAiec = PivotBuilder::matrix(
            $mapped,
            'station',
            'aiec_status',
            null,
            'count'
        );

        // 3. Breakdown: AOC
        $breakdownAoc = PivotBuilder::breakdown(
            $mapped,
            'aoc',
            'AOC / Operator'
        );

        $tables = [
            [
                'title' => '1. Rekapitulasi Station vs Status Pekerjaan CBM',
                'headers' => $matrixStationCbm['headers'],
                'rows' => $matrixStationCbm['rows'],
                'totalRow' => $matrixStationCbm['totalRow'],
            ],
            [
                'title' => '2. Rekapitulasi Station vs Status Pekerjaan AIEC Cleaning',
                'headers' => $matrixStationAiec['headers'],
                'rows' => $matrixStationAiec['rows'],
                'totalRow' => $matrixStationAiec['totalRow'],
            ],
            [
                'title' => '3. Distribusi Pesawat LGT per Operator (AOC)',
                'headers' => $breakdownAoc['headers'],
                'rows' => $breakdownAoc['rows'],
                'totalRow' => $breakdownAoc['totalRow'],
            ],
        ];

        $metadata = [
            'Tanggal LGT' => $this->date ?: 'Semua Tanggal',
            'Filter Station' => $this->station ?: 'Semua Station',
            'Filter Status' => $this->statusFilter ?: 'Semua Status',
            'Total Pesawat LGT' => (string) $records->count(),
            'Tanggal Ekspor' => now()->translatedFormat('d F Y H:i'),
        ];

        $sheets = [
            new PivotRekapSheet('Pivot Rekapitulasi', 'LAPORAN REKAPITULASI LONG GROUND TIME (LGT)', $metadata, $tables),
        ];

        $headings = [
            'Tanggal',
            'Station',
            'Registrasi Pesawat',
            'AOC',
            'STA (Waktu Tiba)',
            'STD (Waktu Berangkat)',
            'Pekerjaan CBM',
            'Man Power CBM',
            'Status CBM',
            'Pekerjaan AIEC',
            'Man Power AIEC',
            'Status AIEC',
            'Keterangan / Alasan',
        ];

        $sheets[] = new DetailDataSheet('Detail Jadwal LGT', $headings, $mapped);

        return $sheets;
    }
}
