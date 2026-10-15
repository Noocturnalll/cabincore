<?php

namespace App\Services\Nsrdi;

use App\Models\NsrdiLog;
use App\Models\NsrdiOverdue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * NSRDI overdue list (weekly workbook) against the data bank.
 *
 * The workbook has a defect list (PROVIDER, A/C, DEFECT, MDDR, STATUS, AREA, REPORTED_DATE, DUE-DATE, ...) and,
 * on another sheet, the part status per document (DOC NO., PART NUMBER, STATUS FINAL, ...).
 *
 * A document is CLOSED by the system when its NSRDI number (the MDDR, or the DEFECT number) is a Closed NSRDI in the
 * data bank: the NSRDI log that the Daily Report archives. It is closed on the date the bank closed it, and keeps the
 * number it matched. Everything else stays open, with its due date (overdue when the date has passed).
 *
 * analyze() only reads and reports; apply() writes.
 */
class NsrdiOverdueSync
{
    /** @var array<string, array{number: string, date: ?string, source: string}>|null normalised number => closed record in the bank */
    private ?array $bank = null;

    public static function normalize(?string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $value));
    }

    /** Closed NSRDI of the data bank, by normalised number. The Daily Report copy (is_submitted) wins over a live one. */
    public function bank(): array
    {
        if ($this->bank !== null) {
            return $this->bank;
        }

        $this->bank = [];
        NsrdiLog::query()->whereNotNull('nsrdi_number')->whereRaw('LOWER(status) = ?', ['closed'])
            ->orderBy('id')->get(['nsrdi_number', 'close_date', 'is_submitted', 'updated_at'])
            ->each(function ($log) {
                $key = self::normalize($log->nsrdi_number);
                if ($key === '') {
                    return;
                }
                $record = [
                    'number' => strtoupper(trim($log->nsrdi_number)),
                    'date' => $log->close_date ? Carbon::parse($log->close_date)->toDateString() : ($log->updated_at?->toDateString()),
                    'source' => $log->is_submitted ? 'daily_report' : 'bank',
                ];
                $kept = $this->bank[$key] ?? null;
                if (! $kept || ($kept['source'] !== 'daily_report' && $record['source'] === 'daily_report')) {
                    $this->bank[$key] = $record;
                }
            });

        return $this->bank;
    }

    /** @return array{number: string, date: ?string, source: string}|null */
    public function match(?string $mddr, ?string $defect): ?array
    {
        $bank = $this->bank();

        foreach ([$mddr, $defect] as $candidate) {
            $key = self::normalize($candidate);
            if ($key !== '' && isset($bank[$key])) {
                return $bank[$key];
            }
        }

        return null;
    }

    // ── reading the workbook ─────────────────────────────────────────────

    /**
     * @return array{rows: array<int, array<string, mixed>>, parts: array<string, array<string, mixed>>}
     */
    public function read(string $path): array
    {
        ini_set('memory_limit', '1G');
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);

        $rows = [];
        $parts = [];

        foreach ($book->getWorksheetIterator() as $sheet) {
            $data = $sheet->toArray(null, true, false, false);
            $header = array_map(fn ($h) => $this->slug((string) $h), array_shift($data) ?? []);
            if (! $header) {
                continue;
            }
            $ix = array_flip($header);

            if (isset($ix['defect']) || isset($ix['mddr'])) {
                foreach ($data as $r) {
                    $get = fn (string ...$names) => $this->clean(collect($names)->map(fn ($n) => isset($ix[$n]) ? ($r[$ix[$n]] ?? null) : null)->first(fn ($v) => $v !== null && trim((string) $v) !== ''));
                    $defect = $get('defect', 'nsrdi_number', 'nsrdi', 'no_nsrdi', 'doc_no');
                    if (! $defect) {
                        continue;
                    }
                    $rows[] = [
                        'operator' => $get('provider', 'operator', 'aoc'),
                        'aircraft_registration' => $get('ac', 'a_c', 'ac_reg', 'aircraft_registration', 'reg'),
                        'ac_status' => $get('status_ac', 'status_a_c', 'ac_status'),
                        'nsrdi_number' => strtoupper($defect),
                        'mddr' => ($m = $get('mddr')) ? strtoupper($m) : null,
                        'status_raw' => $get('status'),
                        'area' => $get('area'),
                        'report_date' => $this->date($r[$ix['reported_date'] ?? $ix['report_date'] ?? -1] ?? null),
                        'due_date' => $this->date($r[$ix['due_date'] ?? -1] ?? null),
                        'month_due' => $get('month_due'),
                        'description' => $get('defect_description', 'description', 'finding_description'),
                    ];
                }
            } elseif (isset($ix['doc_no']) && isset($ix['part_number'])) {
                foreach ($data as $r) {
                    $doc = $this->clean($r[$ix['doc_no']] ?? null);
                    if (! $doc) {
                        continue;
                    }
                    $doc = strtoupper($doc);
                    $parts[$doc] ??= ['part_number' => null, 'status_final' => null, 'remarks' => null, 'count' => 0];
                    $parts[$doc]['count']++;
                    foreach (['part_number' => 'part_number', 'status_final' => 'status_final', 'remarks' => 'remark'] as $to => $from) {
                        $fallback = $to === 'status_final' ? ($ix['status_per_pn'] ?? null) : null;
                        $value = $this->clean($r[$ix[$from] ?? -1] ?? ($fallback !== null ? ($r[$fallback] ?? null) : null));
                        if (! $value && $to === 'status_final' && isset($ix['status_per_pn'])) {
                            $value = $this->clean($r[$ix['status_per_pn']] ?? null);
                        }
                        $parts[$doc][$to] ??= $value;
                    }
                }
            }
        }

        return ['rows' => $rows, 'parts' => $parts];
    }

    // ── analysis and apply ───────────────────────────────────────────────

    /**
     * What an import would do, without doing it.
     *
     * @return array<string, mixed>
     */
    public function analyze(string $path): array
    {
        return $this->run($path, false);
    }

    /**
     * Import the workbook: new documents are added, listed ones are refreshed, documents found closed in the data bank
     * are closed (on the bank's date).
     *
     * @return array<string, mixed>
     */
    public function apply(string $path): array
    {
        return $this->run($path, true);
    }

    /**
     * Close every open document that is closed in the data bank (also used on its own, e.g. after the Daily Report moved).
     *
     * @return array<int, array<string, mixed>> the documents it closed
     */
    public function closeFromBank(): array
    {
        $closed = [];
        NsrdiOverdue::whereRaw('LOWER(status) != ?', ['closed'])->orderBy('id')->each(function (NsrdiOverdue $row) use (&$closed) {
            $hit = $this->match($row->mddr, $row->nsrdi_number);
            if (! $hit) {
                return;
            }
            $row->update([
                'status' => 'Closed',
                'closed_at' => $hit['date'] ? Carbon::parse($hit['date'])->startOfDay() : ($row->closed_at ?: now()),
                'closed_via' => $hit['source'],
                'bank_ref' => $hit['number'],
            ]);
            $closed[] = ['doc' => $row->nsrdi_number, 'mddr' => $row->mddr, 'aircraft' => $row->aircraft_registration, 'bank_ref' => $hit['number'], 'date' => $hit['date'], 'source' => $hit['source']];
        });

        return $closed;
    }

    /**
     * Rows closed by an earlier import carry the import time as closed_at and no reason. Where the data bank closed the
     * same number, record that (and the bank's own date).
     *
     * @return int rows tagged
     */
    public function tagClosedFromBank(): int
    {
        $n = 0;
        NsrdiOverdue::whereRaw('LOWER(status) = ?', ['closed'])->whereNull('closed_via')->orderBy('id')->each(function (NsrdiOverdue $row) use (&$n) {
            $hit = $this->match($row->mddr, $row->nsrdi_number);
            if (! $hit) {
                return;
            }
            $row->update([
                'closed_via' => $hit['source'],
                'bank_ref' => $hit['number'],
                'closed_at' => $hit['date'] ? Carbon::parse($hit['date'])->startOfDay() : $row->closed_at,
            ]);
            $n++;
        });

        return $n;
    }

    private function run(string $path, bool $write): array
    {
        $read = $this->read($path);
        $today = now()->toDateString();

        $seen = [];
        $report = [
            'rows' => count($read['rows']), 'unique' => 0, 'duplicates' => 0,
            'created' => 0, 'updated' => 0, 'closed_by_bank' => [], 'closed_by_file' => 0, 'still_open' => 0,
            'past_due' => 0, 'due_30' => 0, 'by_operator' => [], 'by_month' => [], 'missing_from_file' => 0,
            'parts_found' => count($read['parts']), 'applied' => $write,
        ];

        $existing = NsrdiOverdue::query()->get()->keyBy(fn ($r) => strtoupper(trim((string) $r->nsrdi_number)));

        $apply = function () use ($read, $write, $today, &$seen, &$report, $existing) {
            foreach ($read['rows'] as $row) {
                $doc = $row['nsrdi_number'];
                if (isset($seen[$doc])) {
                    $report['duplicates']++;

                    continue;
                }
                $seen[$doc] = true;
                $report['unique']++;

                $part = $read['parts'][$doc] ?? null;
                $record = $existing->get($doc);
                $hit = $this->match($row['mddr'], $doc);
                $fileClosed = $row['status_raw'] && str_contains(strtoupper($row['status_raw']), 'CLOSE');
                $alreadyClosed = $record && strtolower((string) $record->status) === 'closed';

                $attrs = array_filter([
                    'operator' => $row['operator'], 'aircraft_registration' => $row['aircraft_registration'], 'ac_status' => $row['ac_status'],
                    'mddr' => $row['mddr'], 'area' => $row['area'], 'report_date' => $row['report_date'], 'due_date' => $row['due_date'],
                    'month_due' => $row['month_due'], 'description' => $row['description'],
                    'part_number' => $part['part_number'] ?? null, 'status_final' => $part['status_final'] ?? null, 'remarks' => $part['remarks'] ?? null,
                ], fn ($v) => $v !== null && $v !== '');
                $attrs['last_seen_at'] = $today;

                $closedNow = false;
                if (! $alreadyClosed && $hit) {
                    $attrs += ['status' => 'Closed', 'closed_at' => $hit['date'] ? Carbon::parse($hit['date'])->startOfDay() : now(), 'closed_via' => $hit['source'], 'bank_ref' => $hit['number']];
                    $report['closed_by_bank'][] = ['doc' => $doc, 'mddr' => $row['mddr'], 'aircraft' => $row['aircraft_registration'], 'bank_ref' => $hit['number'], 'date' => $hit['date'], 'source' => $hit['source']];
                    $closedNow = true;
                } elseif (! $alreadyClosed && $fileClosed) {
                    $attrs += ['status' => 'Closed', 'closed_at' => now(), 'closed_via' => 'file'];
                    $report['closed_by_file']++;
                    $closedNow = true;
                } elseif (! $record) {
                    $attrs['status'] = 'Open';
                }

                if ($record) {
                    $report['updated']++;
                    if ($write) {
                        $record->update($attrs);
                    }
                } else {
                    $report['created']++;
                    if ($write) {
                        NsrdiOverdue::create(['nsrdi_number' => $doc] + $attrs);
                    }
                }

                if (! $closedNow && ! $alreadyClosed) {
                    $report['still_open']++;
                    $due = $row['due_date'] ?? $record?->due_date?->toDateString();
                    if ($due && $due < $today) {
                        $report['past_due']++;
                    } elseif ($due && $due <= now()->addDays(30)->toDateString()) {
                        $report['due_30']++;
                    }
                    $op = $row['operator'] ?: '—';
                    $report['by_operator'][$op] = ($report['by_operator'][$op] ?? 0) + 1;
                    $mo = $row['month_due'] ?: '—';
                    $report['by_month'][$mo] = ($report['by_month'][$mo] ?? 0) + 1;
                }
            }
        };

        $write ? DB::transaction($apply) : $apply();

        // open documents that this file no longer lists: probably closed by the planner; reported, never changed
        $report['missing_from_file'] = $existing->filter(fn ($r) => strtolower((string) $r->status) !== 'closed' && ! isset($seen[strtoupper(trim((string) $r->nsrdi_number))]))->count();
        arsort($report['by_operator']);
        ksort($report['by_month']);

        return $report;
    }

    // ── small helpers ────────────────────────────────────────────────────

    private function slug(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($value)), '_');
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $s === '' ? null : $s;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
