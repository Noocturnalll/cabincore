<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\Stretcher;
use App\Models\StretcherTransaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StretcherExport implements Export, FromCollection, WithHeadings, WithMultipleSheets
{
    protected string $search;

    protected string $stretcherFilter;

    protected string $stationFilter;

    protected string $dateFilter;

    public function __construct(string $search = '', string $stretcherFilter = '', string $stationFilter = '', string $dateFilter = '')
    {
        $this->search = $search;
        $this->stretcherFilter = $stretcherFilter;
        $this->stationFilter = $stationFilter;
        $this->dateFilter = $dateFilter;
    }

    public function sheets(): array
    {
        $data = $this->collection();

        $metadata = [
            'Total Transaksi' => number_format($data->count()).' pergerakan',
            'Filter Tanggal' => $this->dateFilter ?: 'Semua Periode',
            'Filter Stretcher' => $this->stretcherFilter ? ('Unit ID: '.$this->stretcherFilter) : 'Semua Unit',
            'Filter Station' => $this->stationFilter ?: 'Semua Station',
            'Pencarian' => $this->search ?: 'Semua Data',
            'Waktu Ekspor' => now()->translatedFormat('d F Y H:i:s'),
        ];

        // 1. Pivot Matrix: Rute x Status Transaksi
        $matrixRoute = PivotBuilder::buildMatrix(
            $data,
            'route',
            'status',
            ['COMPLETED', 'IN_TRANSIT', 'CANCELLED'],
            'Rute Penerbangan',
            '1. Matriks Rekapitulasi: Rute Penerbangan vs Status Transaksi'
        );

        // 2. Breakdown: Origin Station (Bandara Keberangkatan)
        $breakdownOrigin = PivotBuilder::buildBreakdown(
            $data,
            'origin_station',
            'Bandara Asal (Origin)',
            '2. Rekapitulasi Pemakaian per Bandara Keberangkatan'
        );

        // 3. Breakdown: Unit Stretcher
        $breakdownUnit = PivotBuilder::buildBreakdown(
            $data,
            'stretcher_code',
            'Kode Unit Stretcher',
            '3. Rekapitulasi Frekuensi Penggunaan per Unit Stretcher'
        );

        $tables = [$matrixRoute, $breakdownOrigin, $breakdownUnit];

        return [
            new PivotRekapSheet('Pivot Rekapitulasi', 'REKAPITULASI & PIVOT STRETCHER TRACKING', $metadata, $tables),
            new DetailDataSheet($data, $this->headings(), 'Detail Transaksi Stretcher'),
        ];
    }

    public function collection(): Collection
    {
        $query = StretcherTransaction::query()->with('stretcher');

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('flight_number', 'like', "%{$s}%")
                    ->orWhere('aircraft_registration', 'like', "%{$s}%")
                    ->orWhere('route', 'like', "%{$s}%")
                    ->orWhere('passenger_name', 'like', "%{$s}%")
                    ->orWhere('origin_station', 'like', "%{$s}%")
                    ->orWhere('destination_station', 'like', "%{$s}%")
                    ->orWhere('pic_name', 'like', "%{$s}%")
                    ->orWhereHas('stretcher', fn ($sq) => $sq->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"));
            });
        }

        if ($this->stretcherFilter) {
            $query->where('stretcher_id', $this->stretcherFilter);
        }

        if ($this->stationFilter) {
            $st = $this->stationFilter;
            $query->where(fn ($q) => $q->where('origin_station', $st)->orWhere('destination_station', $st));
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        return $query->latest('date')->latest('id')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'date' => $item->date ? $item->date->format('Y-m-d') : null,
                'stretcher_code' => $item->stretcher?->code ?? 'N/A',
                'stretcher_name' => $item->stretcher?->name ?? 'N/A',
                'aircraft_registration' => $item->aircraft_registration,
                'flight_number' => $item->flight_number,
                'origin_station' => $item->origin_station,
                'destination_station' => $item->destination_station,
                'route' => $item->route ?: ($item->origin_station.' - '.$item->destination_station),
                'passenger_name' => $item->passenger_name,
                'medical_notes' => $item->medical_notes,
                'status' => $item->status,
                'pic_name' => $item->pic_name,
                'notes' => $item->notes,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID TRANSAKSI',
            'TANGGAL',
            'KODE STRETCHER',
            'NAMA UNIT',
            'REGISTRASI PESAWAT',
            'NO PENERBANGAN',
            'ORIGIN STA',
            'DESTINATION STA',
            'RUTE',
            'NAMA PASIEN',
            'CATATAN MEDIS',
            'STATUS',
            'PIC / PETUGAS',
            'CATATAN TAMBAHAN',
        ];
    }
}
