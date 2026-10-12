<?php

namespace App\Exports;

use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Services\Attendance\AttendanceScorer;
use Carbon\CarbonInterface;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceExport implements Export, WithMultipleSheets
{
    protected CarbonInterface $from;

    protected CarbonInterface $to;

    protected ?array $stations;

    protected ?string $team;

    protected string $tab;

    public function __construct(
        CarbonInterface $from,
        CarbonInterface $to,
        ?array $stations = null,
        ?string $team = null,
        string $tab = 'overview'
    ) {
        $this->from = $from;
        $this->to = $to;
        $this->stations = $stations;
        $this->team = $team;
        $this->tab = $tab;
    }

    public function sheets(): array
    {
        $scorer = app(AttendanceScorer::class);
        $result = $scorer->build($this->from, $this->to, $this->stations, $this->team);

        $summary = $result['summary'];
        $rows = $result['rows'];
        $sakitList = $result['sakit_list'];
        $cutiList = $result['cuti_list'];
        $alpaList = $result['alpa_list'];
        $resignList = $result['resign_list'];
        $weeklyBreakdown = $result['weekly_breakdown'] ?? [];

        // 1. Pivot Table: Rekap Station vs Status Presensi
        $byStation = $rows->groupBy(fn ($r) => (string) ($r['station'] ?: 'Tanpa Station'));
        $stationRows = [];
        $totPresent = 0;
        $totLate = 0;
        $totSick = 0;
        $totLeave = 0;
        $totAlpha = 0;
        $totWorkDays = 0;

        foreach ($byStation as $stName => $stPeople) {
            $pPresent = $stPeople->sum('present');
            $pLate = $stPeople->sum('late');
            $pSick = $stPeople->sum('sick');
            $pLeave = $stPeople->sum('leave');
            $pAlpha = $stPeople->sum('alpha');
            $pWork = $stPeople->sum('work_days');

            $totPresent += $pPresent;
            $totLate += $pLate;
            $totSick += $pSick;
            $totLeave += $pLeave;
            $totAlpha += $pAlpha;
            $totWorkDays += $pWork;

            $stationRows[] = [
                $stName,
                count($stPeople),
                $pPresent,
                $pLate,
                $pSick,
                $pLeave,
                $pAlpha,
                $pWork,
            ];
        }

        $stationTotalRow = [
            'TOTAL',
            $rows->count(),
            $totPresent,
            $totLate,
            $totSick,
            $totLeave,
            $totAlpha,
            $totWorkDays,
        ];

        // 2. Table Summary Disiplin & Ketidakhadiran
        $onTimeRate = isset($summary['on_time']) && $summary['on_time'] !== null ? $summary['on_time'].'%' : ($summary['on_time_percent'] ?? '-');
        $disiplinRows = [
            ['Rata-rata Tepat Waktu (On-Time)', $rows->count(), '-', $onTimeRate],
            ['Karyawan Terlambat', $rows->filter(fn ($r) => ($r['late'] ?? 0) > 0)->count(), ($summary['late'] ?? $summary['late_total'] ?? 0).' kali', '-'],
            ['Karyawan Sakit', count($sakitList), ($summary['sick'] ?? $summary['sick_total'] ?? 0).' hari', '-'],
            ['Karyawan Cuti', count($cutiList), ($summary['leave'] ?? $summary['leave_total'] ?? 0).' hari', '-'],
            ['Karyawan Alpa', count($alpaList), ($summary['absent'] ?? $summary['alpha_total'] ?? 0).' hari', '-'],
            ['Karyawan Resign', count($resignList), ($summary['period_resign'] ?? count($resignList)).' orang', '-'],
            ['Total Jam Kerja (Man Hours)', $rows->count(), ($summary['total_hours'] ?? $rows->sum('man_hours') ?? 0).' jam', '-'],
        ];

        // 3. Table Weekly Breakdown
        $weeklyRows = [];
        foreach ($weeklyBreakdown as $w) {
            $weeklyRows[] = [
                $w['label'] ?? '',
                $w['range'] ?? '',
                $w['work_days'] ?? 0,
                $w['present'] ?? 0,
                $w['late'] ?? 0,
                $w['sick'] ?? 0,
                $w['leave'] ?? 0,
                $w['alpha'] ?? 0,
                $w['man_hours'] ?? 0,
            ];
        }

        $tables = [
            [
                'title' => '1. Rekapitulasi Presensi per Station',
                'headers' => ['Station', 'Jumlah Staff', 'Hadir', 'Terlambat', 'Sakit', 'Cuti', 'Alpa', 'Total Hari Kerja'],
                'rows' => $stationRows,
                'totalRow' => $stationTotalRow,
            ],
            [
                'title' => '2. Ringkasan Status Disiplin & Ketidakhadiran',
                'headers' => ['Indikator / Kategori Disiplin', 'Jumlah Karyawan', 'Total Kejadian / Jam', 'Tingkat / Keterangan'],
                'rows' => $disiplinRows,
            ],
        ];

        if (! empty($weeklyRows)) {
            $tables[] = [
                'title' => '3. Rekapitulasi Mingguan (Weekly Breakdown)',
                'headers' => ['Minggu', 'Rentang Tanggal', 'Hari Kerja', 'Hadir', 'Terlambat', 'Sakit', 'Cuti', 'Alpa', 'Man Hours'],
                'rows' => $weeklyRows,
            ];
        }

        $metadata = [
            'Periode Presensi' => $this->from->format('d M Y').' s/d '.$this->to->format('d M Y'),
            'Filter Station' => $this->stations ? implode(', ', $this->stations) : 'Semua Station',
            'Filter Tim' => $this->team ?: 'Semua Tim',
            'Total Karyawan' => (string) $rows->count(),
            'Tanggal Ekspor' => now()->translatedFormat('d F Y H:i'),
        ];

        $sheets = [
            new PivotRekapSheet('Pivot Rekapitulasi', 'LAPORAN REKAPITULASI PRESENSI & DISIPLIN', $metadata, $tables),
        ];

        // Sheet 2: Data Detail Karyawan
        $detailCollection = $rows->map(function ($r) {
            $flagNames = array_map(fn ($f) => AttendanceScorer::FLAGS[$f] ?? $f, $r['flags'] ?? []);

            return [
                'nik' => $r['nik'],
                'name' => $r['name'],
                'station' => $r['station'],
                'team' => $r['team'],
                'work_days' => $r['work_days'],
                'present' => $r['present'],
                'on_time_rate' => $r['on_time_rate'].'%',
                'late' => $r['late'],
                'late_minutes' => $r['late_minutes'],
                'sick' => $r['sick'],
                'leave' => $r['leave'],
                'alpha' => $r['alpha'],
                'man_hours' => $r['man_hours'],
                'flags' => implode(', ', $flagNames) ?: 'Normal',
            ];
        });

        $detailHeadings = [
            'NIK',
            'Nama Karyawan',
            'Station',
            'Tim',
            'Hari Kerja',
            'Hadir',
            'Tepat Waktu (%)',
            'Terlambat (kali)',
            'Menit Terlambat',
            'Sakit (hari)',
            'Cuti (hari)',
            'Alpa (hari)',
            'Man Hours',
            'Catatan / Status Disiplin',
        ];

        $sheets[] = new DetailDataSheet('Detail Presensi Karyawan', $detailHeadings, $detailCollection);

        // Sheet 3: Detail Sakit, Cuti, Alpa & Resign
        $nonPresenceRows = collect();

        foreach ($sakitList as $s) {
            $nonPresenceRows->push([
                'status' => 'Sakit',
                'nik' => $s['nik'],
                'name' => $s['name'],
                'station' => $s['station'],
                'team' => $s['team'],
                'count' => $s['count'],
                'dates' => implode(', ', $s['dates']),
                'notes' => implode('; ', $s['notes']),
                'limit' => $s['exceeded_limit'] ? 'Melebihi Batas' : 'Dalam Batas',
            ]);
        }

        foreach ($cutiList as $c) {
            $nonPresenceRows->push([
                'status' => 'Cuti',
                'nik' => $c['nik'],
                'name' => $c['name'],
                'station' => $c['station'],
                'team' => $c['team'],
                'count' => $c['count'],
                'dates' => implode(', ', $c['dates']),
                'notes' => implode('; ', $c['notes']),
                'limit' => $c['exceeded_limit'] ? 'Melebihi Batas' : 'Dalam Batas',
            ]);
        }

        foreach ($alpaList as $a) {
            $nonPresenceRows->push([
                'status' => 'Alpa',
                'nik' => $a['nik'],
                'name' => $a['name'],
                'station' => $a['station'],
                'team' => $a['team'],
                'count' => $a['count'],
                'dates' => implode(', ', $a['dates']),
                'notes' => implode('; ', $a['notes']),
                'limit' => 'Alpa / Tanpa Keterangan',
            ]);
        }

        foreach ($resignList as $res) {
            $nonPresenceRows->push([
                'status' => 'Resign',
                'nik' => $res['nik'],
                'name' => $res['name'],
                'station' => $res['station'],
                'team' => $res['job_title'] ?? $res['department'] ?? '',
                'count' => $res['tenure'] ?? 1,
                'dates' => $res['resign_date'] ?? '–',
                'notes' => ($res['resign_reason'] ?? '').($res['notes'] ? ' - '.$res['notes'] : ''),
                'limit' => 'Resign Resmi',
            ]);
        }

        if ($nonPresenceRows->isNotEmpty()) {
            $nonPresenceHeadings = [
                'Kategori',
                'NIK',
                'Nama Karyawan',
                'Station',
                'Tim / Jabatan',
                'Jumlah Hari / Masa Kerja',
                'Tanggal Kejadian',
                'Keterangan / Alasan',
                'Status Batas Disiplin',
            ];
            $sheets[] = new DetailDataSheet('Detail Sakit Cuti Alpa Resign', $nonPresenceHeadings, $nonPresenceRows);
        }

        return $sheets;
    }
}
