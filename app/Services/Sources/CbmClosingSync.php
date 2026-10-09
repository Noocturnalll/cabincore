<?php

namespace App\Services\Sources;

use App\Models\LeaderReportImport;
use App\Services\Leader\LeaderReportParser;
use App\Services\Leader\LeaderReportReconciler;
use App\Services\Master\MasterSettings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Tab DATA of the CBM workbook: one row per document closed, with the crew (MP 1..n), start and finish.
 * It goes through the same pipeline as an uploaded leader report: matched against the DJA (closed there), the rest
 * saved as unplanned, CML kept as CML, line maintenance left out. A sync whose content did not change creates nothing.
 *
 * Applying is automatic only when Data Master > Pengaturan Integrasi > AUTO_APPLY_CBM_CLOSING is "Ya"; otherwise the
 * result waits as a preview in Laporan Leader, because applying also writes Closed back to the DJA sheet.
 */
class CbmClosingSync
{
    public const FILE_NAME = 'Sheet CBM · DATA';

    public function __construct(private ?SheetFetcher $fetcher = null)
    {
        $this->fetcher ??= app(SheetFetcher::class);
    }

    /** @return array{ok: bool, message: string} */
    public function sync(string $spreadsheetId, string $tab = 'DATA'): array
    {
        $values = $this->fetcher->values($spreadsheetId, $tab);
        if ($values === null) {
            return ['ok' => false, 'message' => $this->fetcher->lastError() ?? 'Tab DATA tidak terbaca.'];
        }

        $book = new Spreadsheet;
        $book->getActiveSheet()->setTitle($tab)->fromArray($values, null, 'A1', true);
        $rows = app(LeaderReportParser::class)->parseWorksheet($book->getActiveSheet(), now());
        $book->disconnectWorksheets();

        if (! $rows) {
            return ['ok' => false, 'message' => 'Tidak ada baris dokumen yang dikenali di tab DATA (butuh NO.DOC dan A/C REG).'];
        }

        $hash = md5(json_encode($rows));
        $last = LeaderReportImport::where('file_name', self::FILE_NAME)->latest('id')->first();
        if ($last && ($last->stats['hash'] ?? null) === $hash) {
            return ['ok' => true, 'message' => 'Tidak ada perubahan sejak sinkron terakhir ('.count($rows).' dokumen).'];
        }

        $import = LeaderReportImport::create([
            'file_name' => self::FILE_NAME,
            'report_date' => collect($rows)->max('work_date') ?: now()->toDateString(),
        ]);
        $now = now();
        foreach (array_chunk($rows, 200) as $chunk) {
            $import->rows()->insert(array_map(fn ($r) => $r + ['import_id' => $import->id, 'created_at' => $now, 'updated_at' => $now], $chunk));
        }

        $reconciler = app(LeaderReportReconciler::class);
        $stats = $reconciler->plan($import) + ['hash' => $hash];
        $import->update(['stats' => $stats]);

        if (app(MasterSettings::class)->flag('AUTO_APPLY_CBM_CLOSING')) {
            $applied = $reconciler->apply($import);
            $import->update(['stats' => $applied + ['hash' => $hash]]);

            return ['ok' => true, 'message' => count($rows).' dokumen diterapkan: '.($applied['closed_planned'] ?? 0).' DJA ditutup, '.(($applied['unplanned_new'] ?? 0) + ($applied['unplanned_update'] ?? 0)).' unplanned, '.(($applied['cml_new'] ?? 0) + ($applied['cml_update'] ?? 0)).' CML.'];
        }

        return ['ok' => true, 'message' => count($rows).' dokumen siap ditinjau di Laporan Leader (belum diterapkan).'];
    }
}
