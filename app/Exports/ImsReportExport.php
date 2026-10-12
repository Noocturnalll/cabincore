<?php

namespace App\Exports;

use App\Exports\Helpers\PivotBuilder;
use App\Exports\Sheets\DetailDataSheet;
use App\Exports\Sheets\PivotRekapSheet;
use App\Models\Ims\StockMovement;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImsReportExport implements Export, WithMultipleSheets
{
    protected ?string $type;

    protected ?string $startDate;

    protected ?string $endDate;

    public function __construct(?string $type = null, ?string $startDate = null, ?string $endDate = null)
    {
        $this->type = $type;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        $query = StockMovement::with(['item.category', 'location'])->orderBy('created_at', 'desc');

        if ($this->type) {
            $query->where('movement_type', $this->type);
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        $records = $query->get();

        $mapped = $records->map(function ($m) {
            return [
                'date' => $m->created_at ? $m->created_at->format('Y-m-d H:i') : '-',
                'part_number' => $m->item?->part_number ?: '-',
                'item_name' => $m->item?->name ?: '-',
                'category' => $m->item?->category?->name ?: 'Umum',
                'location' => $m->location?->name ?: 'Gudang Pusat',
                'movement_type' => strtoupper($m->movement_type ?: 'MUTASI'),
                'before' => (int) ($m->balance_before ?? 0),
                'after' => (int) ($m->balance_after ?? 0),
                'qty_change' => (int) ($m->qty_change ?? 0),
                'notes' => $m->notes ?: '-',
            ];
        });

        // 1. Pivot Table: Lokasi vs Tipe Mutasi
        $matrixLocationType = PivotBuilder::matrix(
            $mapped,
            'location',
            'movement_type',
            null,
            'count'
        );

        // 2. Breakdown: Kategori Barang
        $breakdownCategory = PivotBuilder::breakdown(
            $mapped,
            'category',
            'Kategori Barang'
        );

        // 3. Summary Rekapitulasi Perubahan Qty
        $totalIn = $records->filter(fn ($r) => ($r->qty_change ?? 0) > 0)->sum('qty_change');
        $totalOut = abs($records->filter(fn ($r) => ($r->qty_change ?? 0) < 0)->sum('qty_change'));
        $netChange = $records->sum('qty_change');

        $summaryRows = [
            ['Total Transaksi Mutasi', (string) $records->count(), 'Seluruh transaksi masuk/keluar'],
            ['Total Item Masuk (+)', (string) $totalIn, 'Penerimaan / transfer masuk'],
            ['Total Item Keluar (-)', (string) $totalOut, 'Pengeluaran / peminjaman barang'],
            ['Net Perubahan Stok', (string) $netChange, 'Perubahan bersih stok periode ini'],
        ];

        $tables = [
            [
                'title' => '1. Rekapitulasi Lokasi Gudang/Rak vs Tipe Mutasi',
                'headers' => $matrixLocationType['headers'],
                'rows' => $matrixLocationType['rows'],
                'totalRow' => $matrixLocationType['totalRow'],
            ],
            [
                'title' => '2. Distribusi Frekuensi Mutasi per Kategori Barang',
                'headers' => $breakdownCategory['headers'],
                'rows' => $breakdownCategory['rows'],
                'totalRow' => $breakdownCategory['totalRow'],
            ],
            [
                'title' => '3. Ringkasan Volume Pergerakan Stok (Quantity)',
                'headers' => ['Indikator Mutasi', 'Total Qty / Transaksi', 'Keterangan'],
                'rows' => $summaryRows,
            ],
        ];

        $metadata = [
            'Periode Mutasi' => ($this->startDate ?: 'Awal').' s/d '.($this->endDate ?: 'Sekarang'),
            'Filter Tipe' => $this->type ?: 'Semua Tipe',
            'Total Data' => (string) $records->count(),
            'Tanggal Ekspor' => now()->translatedFormat('d F Y H:i'),
        ];

        $sheets = [
            new PivotRekapSheet('Pivot Rekapitulasi', 'LAPORAN REKAPITULASI MUTASI STOK INVENTORY (IMS)', $metadata, $tables),
        ];

        $headings = [
            'Tanggal',
            'Part Number',
            'Nama Barang',
            'Kategori',
            'Lokasi Gudang / Rak',
            'Tipe Mutasi',
            'Stok Sebelum',
            'Stok Sesudah',
            'Perubahan Qty',
            'Keterangan / Notes',
        ];

        $sheets[] = new DetailDataSheet('Detail Mutasi Stok', $headings, $mapped);

        return $sheets;
    }
}
