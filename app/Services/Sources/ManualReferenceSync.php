<?php

namespace App\Services\Sources;

use App\Models\ManualReference;
use App\Services\GoogleSheetsReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * REF MANUAL workbook: the index of the maintenance manuals (AMM chapters, CMM seat / lavatory / galley, part numbers).
 * Only the index is kept here. A LINK cell is stored as its text plus the hyperlink behind it (a Google Drive file), so
 * the PDF itself never touches this server. Columns are found by their header, so a re-ordered tab still reads right.
 * Read only on the sheet. A tab that comes back empty never wipes what was stored.
 */
class ManualReferenceSync
{
    /** tabs that are handled elsewhere or carry nothing to show */
    private const SKIP = ['COVER', 'SHEET17', 'WORKING GROUP', 'AC MASTER'];

    public function __construct(private ?GoogleSheetsReader $reader = null)
    {
        $this->reader ??= app(GoogleSheetsReader::class);
    }

    /** @return array{ok: bool, message: string} */
    public function sync(string $spreadsheetId, string $tab = ''): array
    {
        if (! $this->reader->isReady()) {
            return ['ok' => false, 'message' => 'Kredensial Google belum diatur (hyperlink hanya bisa dibaca lewat service account).'];
        }

        $tabs = 0;
        $rows = 0;
        $errors = [];

        foreach ($this->reader->tabTitles($spreadsheetId) as $title) {
            if (in_array(strtoupper(trim($title)), self::SKIP, true)) {
                continue;
            }
            try {
                $parsed = self::parse($this->grid($spreadsheetId, $title));
            } catch (\Throwable $e) {
                $errors[] = $title;
                Log::warning("Manual reference: tab {$title} gagal dibaca: ".$e->getMessage());

                continue;
            }
            if ($parsed === []) {
                continue; // empty / unreadable: keep what we have
            }

            DB::transaction(function () use ($title, $parsed) {
                ManualReference::where('tab', $title)->delete();
                foreach (array_chunk($parsed, 200) as $chunk) {
                    ManualReference::insert(array_map(fn ($r, $i) => [
                        'tab' => $title, 'row_no' => $r['row_no'], 'cells' => json_encode($r['cells'], JSON_UNESCAPED_UNICODE),
                        'links' => $r['links'] ? json_encode($r['links'], JSON_UNESCAPED_UNICODE) : null,
                        'search' => $r['search'], 'synced_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                    ], $chunk, array_keys($chunk)));
                }
            });
            $tabs++;
            $rows += count($parsed);
        }

        $message = "{$tabs} tab, {$rows} baris referensi manual diperbarui.";
        if ($errors) {
            $message .= ' Gagal dibaca: '.implode(', ', $errors).'.';
        }

        return ['ok' => $tabs > 0, 'message' => $tabs > 0 ? $message : 'Tidak ada tab yang terbaca.'];
    }

    /**
     * Cells of a tab with their hyperlinks: rows of [text, url|null].
     *
     * @return array<int, array<int, array{0: string, 1: ?string}>>
     */
    protected function grid(string $spreadsheetId, string $tab): array
    {
        $quoted = "'".str_replace("'", "''", $tab)."'";
        $res = $this->reader->service()->spreadsheets->get($spreadsheetId, [
            'ranges' => [$quoted],
            'includeGridData' => true,
            'fields' => 'sheets(data(rowData(values(formattedValue,hyperlink))))',
        ]);

        $grid = [];
        $data = $res->getSheets()[0]->getData()[0] ?? null;
        foreach ($data?->getRowData() ?? [] as $row) {
            $line = [];
            foreach ($row->getValues() ?? [] as $cell) {
                $line[] = [(string) $cell->getFormattedValue(), $cell->getHyperlink() ?: null];
            }
            $grid[] = $line;
        }

        return $grid;
    }

    /**
     * Header row = the first row with at least three filled cells. Rows below become records keyed by header name.
     *
     * @param  array<int, array<int, array{0: string, 1: ?string}>>  $grid
     * @return array<int, array{row_no: int, cells: array<string, string>, links: array<string, array{0: string, 1: string}>, search: string}>
     */
    public static function parse(array $grid): array
    {
        $headerIndex = null;
        foreach (array_slice($grid, 0, 8, true) as $i => $row) {
            if (count(array_filter($row, fn ($c) => trim($c[0]) !== '')) >= 3) {
                $headerIndex = $i;
                break;
            }
        }
        if ($headerIndex === null) {
            return [];
        }

        $names = [];
        foreach ($grid[$headerIndex] as $col => [$text]) {
            $name = trim(preg_replace('/\s+/', ' ', $text));
            if ($name === '') {
                continue;
            }
            $base = $name;
            for ($n = 2; in_array($name, $names, true); $n++) {
                $name = "{$base} {$n}";
            }
            $names[$col] = $name;
        }

        $out = [];
        foreach (array_slice($grid, $headerIndex + 1, null, true) as $i => $row) {
            $cells = [];
            $links = [];
            foreach ($names as $col => $name) {
                [$text, $url] = $row[$col] ?? ['', null];
                $text = trim($text);
                if ($url === null && preg_match('~^https?://\S+$~i', $text)) {
                    $url = $text;
                }
                $cells[$name] = $text;
                if ($url) {
                    $links[$name] = [$text !== '' ? $text : 'Buka', $url];
                }
            }
            if (count(array_filter($cells, fn ($v) => $v !== '')) === 0) {
                continue;
            }
            // a row that only repeats the section title (one cell filled, no link) is kept: it is a section header
            $out[] = [
                'row_no' => $i + 1,
                'cells' => $cells,
                'links' => $links,
                'search' => mb_strtolower(implode(' ', array_filter($cells, fn ($v) => $v !== ''))),
            ];
        }

        return $out;
    }
}
