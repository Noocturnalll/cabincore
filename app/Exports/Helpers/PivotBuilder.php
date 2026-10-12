<?php

namespace App\Exports\Helpers;

use Illuminate\Support\Collection;

class PivotBuilder
{
    /**
     * Builds a 2D matrix pivot table from a collection.
     *
     * @param  string  $rowKey  e.g. 'station'
     * @param  string  $colKey  e.g. 'status'
     * @param  array<int, string>  $preferredCols  e.g. ['Open', 'Closed']
     * @param  string  $rowHeaderLabel  e.g. 'Station / Bandara'
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, mixed>>, totalRow: array<int, mixed>}
     */
    public static function buildMatrix(
        Collection $collection,
        string $rowKey,
        string $colKey,
        array $preferredCols = [],
        string $rowHeaderLabel = 'Station',
        string $tableTitle = 'Matriks Rekapitulasi'
    ): array {
        // Detect distinct column values
        $allColVals = $collection->pluck($colKey)->filter()->map(fn ($v) => (string) $v)->unique()->values()->all();
        if (! empty($preferredCols)) {
            $otherCols = array_diff($allColVals, $preferredCols);
            $columns = array_merge($preferredCols, array_values($otherCols));
        } else {
            $columns = $allColVals;
        }

        if (empty($columns)) {
            $columns = ['Total'];
        }

        $headers = array_merge([$rowHeaderLabel], $columns, ['Total', '%']);

        // Group rows by rowKey
        $grouped = $collection->groupBy(fn ($item) => (string) ($item->{$rowKey} ?? $item[$rowKey] ?? 'N/A'));

        $rows = [];
        $colTotals = array_fill_keys($columns, 0);
        $grandTotal = 0;

        foreach ($grouped as $rowName => $items) {
            $rowCounts = [];
            $rowSum = 0;

            foreach ($columns as $col) {
                $c = $items->filter(fn ($item) => (string) ($item->{$colKey} ?? $item[$colKey] ?? '') === (string) $col)->count();
                $rowCounts[] = $c;
                $rowSum += $c;
                $colTotals[$col] += $c;
            }

            $grandTotal += $rowSum;
            $percent = $collection->count() > 0 ? round(($rowSum / $collection->count()) * 100, 1).'%' : '0%';

            $rows[] = array_merge([(string) $rowName], $rowCounts, [$rowSum, $percent]);
        }

        // Sort rows descending by Total count
        usort($rows, function ($a, $b) use ($headers) {
            $totalIdx = count($headers) - 2;

            return ($b[$totalIdx] ?? 0) <=> ($a[$totalIdx] ?? 0);
        });

        // Build Total Row
        $totalCounts = array_values($colTotals);
        $totalRow = array_merge(['TOTAL'], $totalCounts, [$grandTotal, '100%']);

        return [
            'title' => $tableTitle,
            'headers' => $headers,
            'rows' => $rows,
            'totalRow' => $totalRow,
        ];
    }

    public static function matrix(
        Collection $collection,
        string $rowKey,
        string $colKey,
        ?string $valKey = null,
        string $agg = 'count',
        array $preferredCols = [],
        string $rowHeaderLabel = 'Kategori',
        string $tableTitle = 'Matriks Rekapitulasi'
    ): array {
        return self::buildMatrix($collection, $rowKey, $colKey, $preferredCols, $rowHeaderLabel, $tableTitle);
    }

    public static function breakdown(
        Collection $collection,
        string $groupKey,
        string $groupHeaderLabel = 'Kategori',
        string $tableTitle = 'Rekapitulasi Kategori'
    ): array {
        return self::buildBreakdown($collection, $groupKey, $groupHeaderLabel, $tableTitle);
    }

    /**
     * Builds a simple breakdown table for a single dimension.
     *
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, mixed>>, totalRow: array<int, mixed>}
     */
    public static function buildBreakdown(
        Collection $collection,
        string $groupKey,
        string $groupHeaderLabel = 'Kategori',
        string $tableTitle = 'Rekapitulasi Kategori'
    ): array {
        $grouped = $collection->groupBy(fn ($item) => (string) ($item->{$groupKey} ?? $item[$groupKey] ?? 'Lainnya / Tidak Ditentukan'));

        $rows = [];
        $grandTotal = 0;

        foreach ($grouped as $groupName => $items) {
            $cnt = $items->count();
            $grandTotal += $cnt;
            $rows[] = [
                'name' => (string) $groupName,
                'count' => $cnt,
            ];
        }

        // Sort descending by count
        usort($rows, fn ($a, $b) => $b['count'] <=> $a['count']);

        $formattedRows = [];
        foreach ($rows as $r) {
            $pct = $grandTotal > 0 ? round(($r['count'] / $grandTotal) * 100, 1).'%' : '0%';
            $formattedRows[] = [$r['name'], $r['count'], $pct];
        }

        return [
            'title' => $tableTitle,
            'headers' => [$groupHeaderLabel, 'Jumlah Data', 'Persentase'],
            'rows' => $formattedRows,
            'totalRow' => ['TOTAL', $grandTotal, '100%'],
        ];
    }
}
