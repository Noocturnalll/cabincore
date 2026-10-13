<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\Division;
use App\Models\EmployeeTraining;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TrainingExport implements Export, WithMultipleSheets
{
    public function __construct(
        protected string $search = '',
        protected string $stationFilter = '',
        protected string $companyFilter = '',
        protected string $categoryFilter = '',
        protected string $statusFilter = '',
        protected string $courseFilter = '',
        protected string $divisionFilter = '',
    ) {}

    public function sheets(): array
    {
        @ini_set('memory_limit', '512M');

        $query = EmployeeTraining::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('employee_name', 'like', "%{$this->search}%")
                    ->orWhere('employee_nik', 'like', "%{$this->search}%")
                    ->orWhere('job_title', 'like', "%{$this->search}%")
                    ->orWhere('course_name', 'like', "%{$this->search}%");
            });
        }

        if ($this->stationFilter !== '') {
            $query->where('station', $this->stationFilter);
        }

        if ($this->companyFilter !== '') {
            $query->where('company', $this->companyFilter);
        }

        if ($this->categoryFilter !== '') {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->courseFilter !== '') {
            $query->where('course_name', $this->courseFilter);
        }

        if ($this->divisionFilter !== '') {
            $query->whereHas('employee', fn ($q) => $q->where('division_id', $this->divisionFilter));
        }

        $records = $query->select([
            'employee_nik',
            'employee_name',
            'station',
            'job_title',
            'company',
            'category',
            'course_name',
            'course_date',
            'last_recurrent_date',
            'expiry_date',
            'status',
            'schedule_status',
            'raw_value',
        ])->orderBy('station')->orderBy('employee_name')->orderBy('course_name')->get();

        $mapped = $records->map(function (EmployeeTraining $t) {
            $now = now()->startOfDay();
            $daysLeft = null;
            if ($t->expiry_date) {
                $daysLeft = $now->diffInDays($t->expiry_date, false);
            }

            return [
                'nik' => $t->employee_nik,
                'name' => $t->employee_name,
                'station' => $t->station ?: '-',
                'job_title' => $t->job_title ?: '-',
                'company' => $t->company ?: '-',
                'category' => $t->category,
                'course_name' => $t->course_name,
                'course_date' => $t->course_date ? $t->course_date->format('Y-m-d') : '-',
                'last_recurrent_date' => $t->last_recurrent_date ? $t->last_recurrent_date->format('Y-m-d') : '-',
                'expiry_date' => $t->expiry_date ? $t->expiry_date->format('Y-m-d') : '-',
                'status' => match ($t->status) {
                    'valid' => 'Valid',
                    'expiring_soon' => 'Segera Habis',
                    'expired' => 'Kedaluwarsa',
                    'scheduled' => 'Terjadwal',
                    'unscheduled' => 'Belum Terjadwal',
                    default => ucfirst($t->status),
                },
                'schedule_status' => $t->schedule_status ?: '-',
                'days_left' => $daysLeft !== null ? $daysLeft : '-',
                'raw_value' => $t->raw_value ?: '-',
            ];
        });

        // 1. Pivot Station vs Status
        $matrixStation = PivotBuilder::matrix(
            $mapped,
            'station',
            'status',
            null,
            'count',
            ['Valid', 'Segera Habis', 'Kedaluwarsa', 'Terjadwal', 'Belum Terjadwal'],
            'Station',
            '1. Rekapitulasi Station vs Status Pelatihan'
        );

        // 2. Pivot Kategori vs Status
        $matrixCategory = PivotBuilder::matrix(
            $mapped,
            'category',
            'status',
            null,
            'count',
            ['Valid', 'Segera Habis', 'Kedaluwarsa', 'Terjadwal', 'Belum Terjadwal'],
            'Kategori Pelatihan',
            '2. Rekapitulasi Kategori vs Status Pelatihan'
        );

        // 3. Ringkasan Kepatuhan
        $validCnt = $records->where('status', 'valid')->count();
        $soonCnt = $records->where('status', 'expiring_soon')->count();
        $expCnt = $records->where('status', 'expired')->count();
        $schedCnt = $records->where('status', 'scheduled')->count();
        $unschedCnt = $records->where('status', 'unscheduled')->count();

        $summaryRows = [
            ['Pelatihan Valid / Aktif', $validCnt, 'Kualifikasi personel memenuhi persyaratan'],
            ['Pelatihan Segera Kedaluwarsa (< 60 Hari)', $soonCnt, 'Memerlukan koordinasi penjadwalan recurrent segera'],
            ['Pelatihan Telah Kedaluwarsa (Expired)', $expCnt, 'Kualifikasi tidak berlaku, prioritas refresher'],
            ['Personel Terjadwal (Scheduled)', $schedCnt, 'Tercatat dalam jadwal pelaksanaan pelatihan'],
            ['Personel Belum Terjadwal (Unscheduled)', $unschedCnt, 'Menunggu antrean kuota / slot training'],
        ];

        $tables = [
            $matrixStation,
            $matrixCategory,
            [
                'title' => '3. Ringkasan Kepatuhan & Masa Berlaku Pelatihan',
                'headers' => ['Indikator Kepatuhan', 'Jumlah Rekor', 'Keterangan Operasional'],
                'rows' => $summaryRows,
            ],
        ];

        $metadata = [
            'Total Rekor Pelatihan' => (string) $records->count(),
            'Total Personel Terdata' => (string) $records->pluck('employee_nik')->unique()->count(),
            'Filter Station' => $this->stationFilter ?: 'Semua Station',
            'Filter Divisi' => $this->divisionFilter ? (Division::find($this->divisionFilter)?->name ?: $this->divisionFilter) : 'Semua Divisi',
            'Filter Perusahaan' => $this->companyFilter ?: 'Semua Perusahaan',
            'Filter Kategori' => $this->categoryFilter ?: 'Semua Kategori',
            'Filter Status' => $this->statusFilter ?: 'Semua Status',
            'Tanggal Ekspor' => now()->translatedFormat('d F Y H:i'),
        ];

        $sheets = [
            new PivotRekapSheet('Pivot Rekapitulasi', 'LAPORAN DATABASE TRAINING CABIN MAINTENANCE', $metadata, $tables),
        ];

        // Sheet 2: Detail Data Training
        $detailHeadings = [
            'NIK',
            'Nama Karyawan',
            'Station',
            'Jabatan',
            'Perusahaan',
            'Kategori Pelatihan',
            'Nama Kursus / Pelatihan',
            'Tanggal Pelatihan',
            'Last Recurrent',
            'Masa Berlaku (Expiry)',
            'Status Pelatihan',
            'Status Jadwal',
            'Keterangan / Raw',
        ];

        $detailCollection = $mapped->map(function ($r) {
            return [
                $r['nik'],
                $r['name'],
                $r['station'],
                $r['job_title'],
                $r['company'],
                $r['category'],
                $r['course_name'],
                $r['course_date'],
                $r['last_recurrent_date'],
                $r['expiry_date'],
                $r['status'],
                $r['schedule_status'],
                $r['raw_value'],
            ];
        });

        $sheets[] = new DetailDataSheet('Data Pelatihan', $detailHeadings, $detailCollection);

        // Sheet 3: Recurrent Expiry (HF & SMS)
        $recurrentMapped = $mapped->filter(function ($r) {
            return $r['expiry_date'] !== '-' || in_array($r['status'], ['Segera Habis', 'Kedaluwarsa']);
        });

        $recurrentHeadings = [
            'NIK',
            'Nama Karyawan',
            'Station',
            'Jabatan',
            'Perusahaan',
            'Nama Kursus',
            'Last Recurrent',
            'Masa Berlaku (Expiry)',
            'Sisa Hari',
            'Status',
            'Status Jadwal',
        ];

        $recurrentCollection = $recurrentMapped->map(function ($r) {
            return [
                $r['nik'],
                $r['name'],
                $r['station'],
                $r['job_title'],
                $r['company'],
                $r['course_name'],
                $r['last_recurrent_date'],
                $r['expiry_date'],
                $r['days_left'],
                $r['status'],
                $r['schedule_status'],
            ];
        });

        $sheets[] = new DetailDataSheet('Recurrent Expiry (HF & SMS)', $recurrentHeadings, $recurrentCollection);

        return $sheets;
    }
}
