<?php

namespace App\Imports;

use App\Services\Dja\DjaIngestor;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;

/**
 * Excel import of one planner tab. Uses exactly the same mapping, classification and storage as the
 * Google Sheets sync (App\Services\Dja\*), so a file and the live sheet can never disagree.
 */
class DjaSheetImport implements ToModel
{
    /** Header cells that identify a title row rather than data. */
    private const HEADER_LABELS = ['DATE', 'AC REG', 'A/C REG', 'TASK ID', 'DESCRIPTION', 'WO NUMBER', 'NO WO'];

    public function __construct(protected string $tabName, protected ?DjaIngestor $ingestor = null)
    {
        $this->ingestor ??= app(DjaIngestor::class);
    }

    public function model(array $row): Model|array|null
    {
        if ($this->looksLikeHeader($row)) {
            return null;
        }

        $this->ingestor->ingest($this->tabName, $row, [], null);

        return null;
    }

    private function looksLikeHeader(array $row): bool
    {
        foreach (array_slice($row, 0, 8) as $cell) {
            if (is_string($cell) && in_array(strtoupper(trim($cell)), self::HEADER_LABELS, true)) {
                return true;
            }
        }

        return false;
    }
}
