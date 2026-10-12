<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PivotRekapSheet implements Export, FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected string $sheetTitle;

    protected string $reportTitle;

    /** @var array<string, string> */
    protected array $metadata;

    /** @var array<int, array{title: string, headers: array<int, string>, rows: array<int, array<int, mixed>>, totalRow?: array<int, mixed>}> */
    protected array $tables;

    /** @var array<int, string> */
    protected array $boldRowTypes = [];

    public function __construct(string $sheetTitle, string $reportTitle, array $metadata, array $tables)
    {
        $this->sheetTitle = $sheetTitle;
        $this->reportTitle = $reportTitle;
        $this->metadata = $metadata;
        $this->tables = $tables;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function array(): array
    {
        $data = [];

        // 1. Title Block
        $data[] = [$this->reportTitle];
        $this->boldRowTypes[count($data)] = 'main_title';

        foreach ($this->metadata as $label => $val) {
            $data[] = [$label.':', $val];
            $this->boldRowTypes[count($data)] = 'meta';
        }

        $data[] = ['']; // Empty separator

        // 2. Tables
        foreach ($this->tables as $tbl) {
            // Table Section Header
            $data[] = [$tbl['title']];
            $this->boldRowTypes[count($data)] = 'table_title';

            // Table Column Headers
            $data[] = $tbl['headers'];
            $this->boldRowTypes[count($data)] = 'table_header';

            // Table Data Rows
            foreach ($tbl['rows'] as $r) {
                $data[] = $r;
            }

            // Table Total Row (if provided)
            if (! empty($tbl['totalRow'])) {
                $data[] = $tbl['totalRow'];
                $this->boldRowTypes[count($data)] = 'table_total';
            }

            $data[] = ['']; // Spacing between tables
        }

        return $data;
    }

    public function styles(Worksheet $sheet): ?array
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0F172A']],
            ],
        ];

        foreach ($this->boldRowTypes as $rowNum => $type) {
            switch ($type) {
                case 'table_title':
                    $sheet->getStyle("A{$rowNum}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1E293B']],
                    ]);
                    break;
                case 'table_header':
                    $highestCol = $sheet->getHighestColumn($rowNum);
                    $sheet->getStyle("A{$rowNum}:{$highestCol}{$rowNum}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '1E3A8A'], // Deep Blue Navy
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                    break;
                case 'table_total':
                    $highestCol = $sheet->getHighestColumn($rowNum);
                    $sheet->getStyle("A{$rowNum}:{$highestCol}{$rowNum}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E2E8F0'], // Light slate total
                        ],
                        'borders' => [
                            'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                            'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '475569']],
                        ],
                    ]);
                    break;
            }
        }

        return $styles;
    }
}
