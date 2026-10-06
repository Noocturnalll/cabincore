<?php

namespace App\Services;

use Carbon\Carbon;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use RuntimeException;

/**
 * Thin wrapper around the Google Sheets API shared by all pull-sync services.
 */
class GoogleSheetsReader
{
    protected ?Sheets $service = null;

    public function __construct()
    {
        $credentialsPath = self::credentialsPath();

        if (! class_exists(Client::class) || ! file_exists($credentialsPath)) {
            Log::warning("Google credentials not found at {$credentialsPath}");

            return;
        }

        $client = new Client;
        $client->setApplicationName('Cabin Core Sync');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        $client->setAuthConfig($credentialsPath);

        $this->service = new Sheets($client);
    }

    public static function credentialsPath(): string
    {
        return storage_path('app/google-credentials.json');
    }

    public function isReady(): bool
    {
        return $this->service !== null;
    }

    public function service(): Sheets
    {
        if (! $this->service) {
            throw new RuntimeException('Google Sheets belum terkonfigurasi: file storage/app/google-credentials.json tidak ditemukan.');
        }

        return $this->service;
    }

    /**
     * Returns the real tab titles in the spreadsheet (throws on 403/404 so callers can report access problems).
     *
     * @return array<int, string>
     */
    public function tabTitles(string $spreadsheetId): array
    {
        $spreadsheet = $this->service()->spreadsheets->get($spreadsheetId, ['fields' => 'sheets.properties.title']);

        return collect($spreadsheet->getSheets())
            ->map(fn ($sheet) => $sheet->getProperties()->getTitle())
            ->all();
    }

    /**
     * Matches wanted tab names against actual titles, ignoring case and surrounding whitespace.
     *
     * @param  array<int, string>  $wantedTabs
     * @param  array<int, string>  $actualTitles
     * @return array<string, string> wanted name => actual title
     */
    public function resolveTabs(array $wantedTabs, array $actualTitles): array
    {
        $normalized = [];
        foreach ($actualTitles as $title) {
            $normalized[self::normalizeName($title)] = $title;
        }

        $resolved = [];
        foreach ($wantedTabs as $wanted) {
            $key = self::normalizeName($wanted);
            if (isset($normalized[$key])) {
                $resolved[$wanted] = $normalized[$key];
            }
        }

        return $resolved;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function values(string $spreadsheetId, string $tabTitle): array
    {
        $range = "'".str_replace("'", "''", $tabTitle)."'";

        $response = $this->service()->spreadsheets_values->get($spreadsheetId, $range, [
            'valueRenderOption' => 'FORMATTED_VALUE',
        ]);

        return $response->getValues() ?? [];
    }

    /**
     * Finds the header row within the first rows of a sheet: the row matching the most known header names.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, string>  $knownHeaders
     * @return array{index: int, map: array<string, int>}|null
     */
    public function locateHeader(array $rows, array $knownHeaders, int $minimumMatches = 2, int $scanRows = 15): ?array
    {
        $known = array_map([self::class, 'normalizeName'], $knownHeaders);
        $best = null;

        foreach (array_slice($rows, 0, $scanRows, true) as $index => $row) {
            $map = [];
            foreach ($row as $col => $cell) {
                $name = self::normalizeName((string) $cell);
                if ($name !== '' && ! isset($map[$name])) {
                    $map[$name] = $col;
                }
            }

            $matches = count(array_intersect(array_keys($map), $known));
            if ($matches >= $minimumMatches && ($best === null || $matches > $best['matches'])) {
                $best = ['index' => $index, 'map' => $map, 'matches' => $matches];
            }
        }

        return $best ? ['index' => $best['index'], 'map' => $best['map']] : null;
    }

    public static function normalizeName(string $value): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', $value)));
    }

    public static function parseDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 20000) {
            return Carbon::instance(Date::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y', 'd M Y', 'd F Y', 'j M Y', 'j F Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public static function parseInteger(mixed $value): ?int
    {
        $value = trim((string) $value);

        return preg_match('/^\d+/', $value, $matches) ? (int) $matches[0] : null;
    }

    public static function cleanString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
