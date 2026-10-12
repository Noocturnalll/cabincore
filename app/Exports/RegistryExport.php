<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\RegistryRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RegistryExport implements Export, FromCollection, WithHeadings, WithMultipleSheets
{
    protected string $module;

    protected string $search;

    protected string $divisionFilter;

    protected bool $onlyDue;

    public function __construct(string $module, string $search = '', string $divisionFilter = '', bool $onlyDue = false)
    {
        $this->module = $module;
        $this->search = $search;
        $this->divisionFilter = $divisionFilter;
        $this->onlyDue = $onlyDue;
    }

    private function cfg(): array
    {
        return config("registry.modules.{$this->module}", []);
    }

    public function sheets(): array
    {
        $cfg = $this->cfg();
        $label = $cfg['label'] ?? strtoupper($this->module);
        $data = $this->collection();

        $metadata = [
            'Modul Registry' => $label,
            'Deskripsi' => $cfg['subtitle'] ?? '-',
            'Total Record' => number_format($data->count()).' data',
            'Filter Divisi' => $this->divisionFilter ? ('Divisi ID: '.$this->divisionFilter) : 'Semua Divisi',
            'Filter Masa Berlaku' => $this->onlyDue ? 'Hanya Mendekati Habis / Kedaluwarsa' : 'Semua',
            'Pencarian' => $this->search ?: 'Semua Data',
            'Waktu Ekspor' => now()->translatedFormat('d F Y H:i:s'),
        ];

        $tables = [];

        // 1. Module-specific pivot logic
        if ($this->module === 'training') {
            // Matrix: Divisi vs Status Kehadiran
            $tables[] = PivotBuilder::buildMatrix(
                $data,
                'division_name',
                'attendance_status',
                ['Hadir', 'Tidak Hadir', 'Terjadwal'],
                'Divisi',
                '1. Matriks Rekapitulasi: Divisi vs Status Kehadiran Training'
            );

            // Breakdown: Alasan Tidak Hadir (Sakit, Dinas, Cuti, Others)
            $nonAttendees = $data->filter(fn ($item) => ($item['attendance_status'] ?? '') === 'Tidak Hadir');
            if ($nonAttendees->isNotEmpty()) {
                $tables[] = PivotBuilder::buildBreakdown(
                    $nonAttendees,
                    'absence_reason',
                    'Alasan Tidak Hadir',
                    '2. Rekapitulasi Alasan Tidak Hadir (Sakit / Dinas / Cuti / Others)'
                );
            }
        } elseif (! empty($cfg['due'])) {
            // Matrix: Divisi vs Status Masa Berlaku (Expiry Status)
            $tables[] = PivotBuilder::buildMatrix(
                $data,
                'division_name',
                'expiry_status',
                ['Valid', 'Segera Berakhir', 'Kritis / Kedaluwarsa'],
                'Divisi',
                '1. Matriks Rekapitulasi: Divisi vs Masa Berlaku ('.($cfg['fields'][$cfg['due']][0] ?? 'Expired').')'
            );
        } else {
            // General Matrix: Divisi vs Status (or Title)
            $statusKey = isset($cfg['fields']['status']) ? 'status' : 'division_name';
            $tables[] = PivotBuilder::buildBreakdown(
                $data,
                'division_name',
                'Divisi',
                '1. Rekapitulasi Jumlah Data per Divisi'
            );
        }

        return [
            new PivotRekapSheet('Pivot Rekapitulasi', 'REKAPITULASI & PIVOT '.strtoupper($label), $metadata, $tables),
            new DetailDataSheet($data, $this->headings(), 'Data Detail '.substr($label, 0, 20)),
        ];
    }

    public function collection(): Collection
    {
        $cfg = $this->cfg();
        $fields = $cfg['fields'] ?? [];
        $hasDue = ! empty($cfg['due']);
        $user = auth()->user();
        $seesAll = $user ? $user->can('registry.view_all') : true;

        $query = RegistryRecord::query()
            ->where('module', $this->module)
            ->with('division')
            ->when($user && ! $seesAll, fn ($q) => $q->where('division_id', $user->division_id))
            ->when($this->divisionFilter !== '' && $seesAll, fn ($q) => $q->where('division_id', $this->divisionFilter))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', $term)
                        ->orWhere('data', 'like', $term);
                });
            });

        if ($this->onlyDue && $hasDue) {
            $cutoff = now()->addMonths((int) ($cfg['yellow'] ?? 2))->toDateString();
            $query->whereNotNull('due_date')->where('due_date', '<=', $cutoff);
        }

        return $query->latest('id')->get()->map(function ($record) use ($cfg, $hasDue) {
            $state = $record->expiryStatus();
            $stateLabel = match ($state) {
                'yellow' => 'Segera Berakhir',
                'red', 'expired' => 'Kritis / Kedaluwarsa',
                default => 'Valid',
            };

            $row = [
                'id' => $record->id,
                'division_name' => $record->division?->name ?? 'N/A',
                'title' => $record->title,
            ];

            foreach ($cfg['columns'] ?? [] as $col) {
                $val = $record->data[$col] ?? null;
                $row[$col] = is_array($val) ? json_encode($val) : $val;
            }

            if ($hasDue) {
                $row['due_date'] = $record->due_date ? $record->due_date->format('Y-m-d') : null;
                $row['expiry_status'] = $stateLabel;
            }

            // Include helper attributes for pivot
            $row['attendance_status'] = $record->data['attendance_status'] ?? 'Terjadwal';
            $row['absence_reason'] = $record->data['absence_reason'] ?? 'None';

            return $row;
        });
    }

    public function headings(): array
    {
        $cfg = $this->cfg();
        $cols = $cfg['columns'] ?? [];
        $fields = $cfg['fields'] ?? [];

        $headers = ['ID', 'DIVISI', 'TITLE'];
        foreach ($cols as $c) {
            $headers[] = strtoupper($fields[$c][0] ?? $c);
        }

        if (! empty($cfg['due'])) {
            $headers[] = strtoupper($fields[$cfg['due']][0] ?? 'BERLAKU S/D');
            $headers[] = 'STATUS MASA BERLAKU';
        }

        return $headers;
    }
}
