<?php

namespace App\Services\Compliance;

use App\Models\ComplianceEntry;
use App\Models\SyncSetting;
use App\Services\GoogleSheetsReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors the compliance spreadsheet ("Entries" tab: one row per station, date and shift with links to the three
 * photos kept on Drive) into compliance_entries. Read only: nothing is written back to the sheet.
 *
 * Reads through the service account when it is set up, otherwise through the sheet's public CSV export
 * (works when the sheet is shared as "anyone with the link").
 */
class ComplianceSyncService
{
    public const Key = 'compliance';

    protected ?string $lastError = null;

    public function __construct(private ?GoogleSheetsReader $reader = null) {}

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function spreadsheetId(): string
    {
        return SyncSetting::spreadsheetIdFor(self::Key) ?: config('compliance.default_spreadsheet');
    }

    /** @return array{read: int, created: int, updated: int}|null null when the sheet could not be read */
    public function sync(): ?array
    {
        $this->lastError = null;
        $rows = $this->fetchRows($this->spreadsheetId());
        if ($rows === null) {
            SyncSetting::recordResult(self::Key, false, $this->lastError ?? 'Gagal membaca sheet compliance.');

            return null;
        }

        $stats = $this->store($rows);
        SyncSetting::recordResult(self::Key, true, "{$stats['read']} entri dibaca, {$stats['created']} baru, {$stats['updated']} diperbarui.");

        return $stats;
    }

    /** @param  array<int, array<int, mixed>>  $rows  header row first */
    public function store(array $rows): array
    {
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), array_shift($rows) ?? []);
        $ix = array_flip($header);
        foreach (['entryid', 'stasiun', 'tanggal', 'shift'] as $required) {
            if (! isset($ix[$required])) {
                $this->lastError = "Kolom {$required} tidak ada di tab Entries.";

                return ['read' => 0, 'created' => 0, 'updated' => 0];
            }
        }

        $stats = ['read' => 0, 'created' => 0, 'updated' => 0];
        $get = fn (array $r, string $col) => isset($ix[$col], $r[$ix[$col]]) && trim((string) $r[$ix[$col]]) !== '' ? trim((string) $r[$ix[$col]]) : null;

        foreach ($rows as $r) {
            $entryId = $get($r, 'entryid');
            $date = $get($r, 'tanggal');
            if (! $entryId || ! $date) {
                continue;
            }
            $stats['read']++;

            $entry = ComplianceEntry::firstOrNew(['entry_id' => $entryId]);
            $created = ! $entry->exists;
            $entry->fill([
                'station' => strtoupper((string) $get($r, 'stasiun')),
                'work_date' => Carbon::parse($date)->toDateString(),
                'shift' => ucfirst(strtolower((string) $get($r, 'shift'))),
                'url_5r' => $get($r, 'url_5r'),
                'url_att' => $get($r, 'url_att'),
                'url_brf' => $get($r, 'url_brf'),
                'submitted_at' => $this->timestamp($get($r, 'timestamp')),
            ]);
            if ($created || $entry->isDirty()) {
                $entry->save();
                $stats[$created ? 'created' : 'updated']++;
            }
        }

        return $stats;
    }

    /** @return array<int, array<int, mixed>>|null */
    private function fetchRows(string $spreadsheetId): ?array
    {
        $tab = config('compliance.tab');

        $reader = $this->reader ?? app(GoogleSheetsReader::class);
        if ($reader->isReady()) {
            try {
                return $reader->values($spreadsheetId, $tab);
            } catch (\Throwable $e) {
                Log::warning('Compliance sync via service account failed, trying public export: '.$e->getMessage());
            }
        }

        try {
            $response = Http::timeout(60)->get("https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq", ['tqx' => 'out:csv', 'sheet' => $tab]);
        } catch (\Throwable $e) {
            $this->lastError = 'Sheet compliance tidak bisa dihubungi: '.$e->getMessage();

            return null;
        }
        if (! $response->successful() || str_starts_with(ltrim($response->body()), '<')) {
            $this->lastError = 'Sheet compliance tidak bisa dibaca. Bagikan sebagai "Anyone with the link" atau ke akun service account.';

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

    /** "8/10/2026, 09.47.58" (d/m/Y, H.i.s) as written by the Apps Script. */
    private function timestamp(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        foreach (['j/n/Y, H.i.s', 'j/n/Y H.i.s', 'Y-m-d H:i:s'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed) {
                    return $parsed->toDateTimeString();
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }
}
