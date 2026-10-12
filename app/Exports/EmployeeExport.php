<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\Employee;
use App\Models\RegistryRecord;
use App\Support\ExpiryStatus;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeeExport implements Export, WithMultipleSheets
{
    protected string $search;

    protected string $divisionFilter;

    protected string $expiryFilter;

    protected ?array $allowedDivisionIds;

    public function __construct(
        string $search = '',
        string $divisionFilter = '',
        string $expiryFilter = '',
        ?array $allowedDivisionIds = null
    ) {
        $this->search = $search;
        $this->divisionFilter = $divisionFilter;
        $this->expiryFilter = $expiryFilter;
        $this->allowedDivisionIds = $allowedDivisionIds;
    }

    public function sheets(): array
    {
        $query = Employee::query()->with(['division', 'position']);

        if ($this->allowedDivisionIds !== null) {
            $query->whereIn('division_id', $this->allowedDivisionIds);
        }

        if ($this->search !== '') {
            $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('nik', 'like', "%{$this->search}%")
                ->orWhere('job_title', 'like', "%{$this->search}%")
                ->orWhere('station', 'like', "%{$this->search}%")
                ->orWhere('passport_no', 'like', "%{$this->search}%"));
        }

        if ($this->divisionFilter !== '') {
            $query->where('division_id', $this->divisionFilter);
        }

        $windows = fn (string $key) => now()->startOfDay()->addMonthsNoOverflow(config("hr.$key.yellow"))->toDateString();
        if ($this->expiryFilter === 'contract') {
            $query->where('contract_type', 'PKWT')->whereDate('contract_end', '<=', $windows('contract'));
        } elseif ($this->expiryFilter === 'passport') {
            $query->whereDate('passport_expiry', '<=', $windows('passport'));
        } elseif ($this->expiryFilter === 'any') {
            $query->where(fn ($w) => $w
                ->where(fn ($c) => $c->where('contract_type', 'PKWT')->whereDate('contract_end', '<=', $windows('contract')))
                ->orWhereDate('passport_expiry', '<=', $windows('passport')));
        }

        $employees = $query->orderBy('name')->get();

        // PAS Bandara lookup
        $pasRows = RegistryRecord::where('module', 'pas')
            ->whereIn('data->nik', $employees->pluck('nik')->filter()->all())
            ->get();
        $pasByNik = $pasRows->keyBy(fn ($r) => (string) ($r->data['nik'] ?? ''));

        // Prepare mapped collection for Pivot and Detail
        $mapped = $employees->map(function (Employee $e) use ($pasByNik) {
            $pasRecord = $pasByNik->get($e->nik);
            $pasStatus = $pasRecord ? $pasRecord->expiryStatus() : ExpiryStatus::NONE;

            return [
                'nik' => $e->nik,
                'name' => $e->name,
                'division_name' => $e->division?->name ?: 'Tanpa Divisi',
                'position_name' => $e->position?->name ?: ($e->job_title ?: '-'),
                'station' => $e->station ?: '-',
                'phone' => $e->phone ?: '-',
                'join_date' => $e->join_date ? $e->join_date->format('Y-m-d') : '-',
                'status' => $e->status ?: 'Aktif',
                'contract_type' => $e->contract_type ?: 'PKWT',
                'contract_start' => $e->contract_start ? $e->contract_start->format('Y-m-d') : '-',
                'contract_end' => $e->contract_end ? $e->contract_end->format('Y-m-d') : '-',
                'contract_status' => $e->contractStatus(),
                'passport_no' => $e->passport_no ?: '-',
                'passport_expiry' => $e->passport_expiry ? $e->passport_expiry->format('Y-m-d') : '-',
                'passport_status' => $e->passportStatus(),
                'pas_status' => $pasStatus,
                'resign_date' => $e->resign_date ? $e->resign_date->format('Y-m-d') : '-',
                'resign_reason' => $e->resign_reason ?: '-',
                'notes' => $e->notes ?: '',
            ];
        });

        // 1. Pivot Table: Divisi vs Status Karyawan
        $matrixDivStatus = PivotBuilder::matrix(
            $mapped,
            'division_name',
            'status',
            null,
            'count'
        );

        // 2. Pivot Table: Divisi vs Tipe Kontrak
        $matrixDivContract = PivotBuilder::matrix(
            $mapped,
            'division_name',
            'contract_type',
            null,
            'count'
        );

        // 3. Summary Expiry Documents
        $contractYellow = $employees->filter(fn ($e) => $e->contractStatus() === ExpiryStatus::YELLOW)->count();
        $contractRed = $employees->filter(fn ($e) => in_array($e->contractStatus(), [ExpiryStatus::RED, ExpiryStatus::EXPIRED]))->count();
        $passportYellow = $employees->filter(fn ($e) => $e->passportStatus() === ExpiryStatus::YELLOW)->count();
        $passportRed = $employees->filter(fn ($e) => in_array($e->passportStatus(), [ExpiryStatus::RED, ExpiryStatus::EXPIRED]))->count();
        $pasYellow = $pasRows->filter(fn ($r) => $r->expiryStatus() === ExpiryStatus::YELLOW)->count();
        $pasRed = $pasRows->filter(fn ($r) => in_array($r->expiryStatus(), [ExpiryStatus::RED, ExpiryStatus::EXPIRED]))->count();

        $expirySummaryRows = [
            ['Kontrak PKWT Segera Habis (Kuning)', $contractYellow, 'Mendekati masa akhir kontrak kerja'],
            ['Kontrak PKWT Kritis / Habis (Merah)', $contractRed, 'Perlu perpanjangan atau penyelesaian'],
            ['Paspor Segera Habis (Kuning)', $passportYellow, 'Mendekati masa kedaluwarsa paspor'],
            ['Paspor Kritis / Habis (Merah)', $passportRed, 'Paspor telah habis / kritis'],
            ['PAS Bandara Segera Habis (Kuning)', $pasYellow, 'Mendekati masa habis pass bandara'],
            ['PAS Bandara Kritis / Habis (Merah)', $pasRed, 'Pass bandara telah habis / kritis'],
        ];

        $tables = [
            [
                'title' => '1. Rekapitulasi Divisi vs Status Karyawan',
                'headers' => $matrixDivStatus['headers'],
                'rows' => $matrixDivStatus['rows'],
                'totalRow' => $matrixDivStatus['totalRow'],
            ],
            [
                'title' => '2. Rekapitulasi Divisi vs Tipe Kontrak',
                'headers' => $matrixDivContract['headers'],
                'rows' => $matrixDivContract['rows'],
                'totalRow' => $matrixDivContract['totalRow'],
            ],
            [
                'title' => '3. Ringkasan Status Kedaluwarsa Dokumen Personel',
                'headers' => ['Indikator Dokumen', 'Jumlah Personel', 'Keterangan'],
                'rows' => $expirySummaryRows,
            ],
        ];

        $metadata = [
            'Total Karyawan' => (string) $employees->count(),
            'Filter Pencarian' => $this->search ?: 'Semua',
            'Filter Divisi' => $this->divisionFilter ? ($employees->first()?->division?->name ?: $this->divisionFilter) : 'Semua Divisi',
            'Filter Masa Berlaku' => $this->expiryFilter ?: 'Semua Dokumen',
            'Tanggal Ekspor' => now()->translatedFormat('d F Y H:i'),
        ];

        $sheets = [
            new PivotRekapSheet('Pivot Rekapitulasi', 'LAPORAN DATABASE KARYAWAN & MONITORING DOKUMEN', $metadata, $tables),
        ];

        // Sheet 2: Data Detail Karyawan
        $detailHeadings = [
            'NIK',
            'Nama Karyawan',
            'Divisi',
            'Jabatan / Posisi',
            'Station',
            'No Telepon',
            'Tanggal Masuk',
            'Status Karyawan',
            'Tipe Kontrak',
            'Kontrak Mulai',
            'Kontrak Berakhir',
            'Status Kontrak',
            'No Paspor',
            'Masa Berlaku Paspor',
            'Status Paspor',
            'Status PAS Bandara',
            'Tanggal Resign',
            'Alasan Resign',
            'Catatan',
        ];

        $detailCollection = $mapped->map(function ($r) {
            return [
                $r['nik'],
                $r['name'],
                $r['division_name'],
                $r['position_name'],
                $r['station'],
                $r['phone'],
                $r['join_date'],
                $r['status'],
                $r['contract_type'],
                $r['contract_start'],
                $r['contract_end'],
                $r['contract_status'],
                $r['passport_no'],
                $r['passport_expiry'],
                $r['passport_status'],
                $r['pas_status'],
                $r['resign_date'],
                $r['resign_reason'],
                $r['notes'],
            ];
        });

        $sheets[] = new DetailDataSheet('Data Karyawan', $detailHeadings, $detailCollection);

        return $sheets;
    }
}
