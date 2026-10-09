<?php

namespace App\Services\Sources;

use App\Services\GoogleSheetsReader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads one tab of a Google Sheet as a grid of strings. Uses the service account when it is set up (private sheets),
 * otherwise the sheet's public CSV export (sheets shared as "anyone with the link"). Read only.
 */
class SheetFetcher
{
    protected ?string $lastError = null;

    public function __construct(private ?GoogleSheetsReader $reader = null) {}

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /** @return array<int, array<int, mixed>>|null null when the tab could not be read (see lastError) */
    public function values(string $spreadsheetId, string $tab): ?array
    {
        $this->lastError = null;

        $reader = $this->reader ?? app(GoogleSheetsReader::class);
        if ($reader->isReady()) {
            try {
                $titles = $reader->tabTitles($spreadsheetId);
                $actual = collect($reader->resolveTabs([$tab], $titles))->first();
                if ($actual) {
                    return $reader->values($spreadsheetId, $actual);
                }
                $this->lastError = "Tab \"{$tab}\" tidak ada di spreadsheet.";

                return null;
            } catch (\Throwable $e) {
                Log::warning('Sheet read via service account failed, trying public export: '.$e->getMessage());
            }
        }

        try {
            $response = Http::timeout(90)->get("https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq", ['tqx' => 'out:csv', 'sheet' => $tab]);
        } catch (\Throwable $e) {
            $this->lastError = 'Sheet tidak bisa dihubungi: '.$e->getMessage();

            return null;
        }
        if (! $response->successful() || str_starts_with(ltrim($response->body()), '<')) {
            $this->lastError = 'Sheet tidak bisa dibaca. Bagikan sebagai "Anyone with the link" atau ke akun service account.';

            return null;
        }

        $rows = [];
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $response->body());
        rewind($stream);
        while (($line = fgetcsv($stream)) !== false) {
            $rows[] = $line;
        }
        fclose($stream);

        return $rows;
    }
}
