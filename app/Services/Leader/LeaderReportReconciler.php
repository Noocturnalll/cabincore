<?php

namespace App\Services\Leader;

use App\Livewire\Traits\ManagesLogStatus;
use App\Models\CmlLog;
use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\LeaderReportImport;
use App\Models\LeaderReportRow;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Services\Crew\CrewWriter;
use App\Services\Dja\ClassificationMemory;
use App\Services\Dja\DjaClassifier;
use App\Services\GoogleSheetsSyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Matches leader report rows against the official DJA work.
 *
 *   document found in DJA (log with dja_id)  -> that DJA log is closed (or, if the leader reports Open, only the
 *                                               man power / times are filled; status changes need reason + code)
 *   WO / NSRDI / DMI not in DJA              -> saved as an unplanned log (dja_id empty)
 *   CML                                      -> saved as CML (matched on document number, so re-imports update)
 *
 * plan() decides an outcome per row without touching the logs; apply() performs it. Re-importing the same file is safe.
 */
class LeaderReportReconciler
{
    public const OUTCOMES = [
        'closed_planned' => 'DJA ditutup (Closed)',
        'updated_planned' => 'DJA dilengkapi (MP / jam)',
        'unplanned_new' => 'Unplanned baru',
        'unplanned_update' => 'Unplanned diperbarui',
        'cml_new' => 'CML baru',
        'cml_update' => 'CML diperbarui',
        'line_maintenance' => 'Line Maintenance (tidak dimasukkan)',
        'skipped' => 'Dilewati',
    ];

    /** Must equal ManagesLogStatus::REASON_CODES (covered by a test). */
    public const REASON_CODES = ['AUTHOR', 'DEFFECT', 'GSE', 'IRR', 'LT', 'MP', 'NS', 'NT', 'OCT', 'TC', 'WT'];

    private const MODELS = [
        'WO' => [WoLog::class, 'wo_number', 'wo_logs'],
        'DMI' => [DmiLog::class, 'dmi_number', 'dmi_logs'],
        'NSRDI' => [NsrdiLog::class, 'nsrdi_number', 'nsrdi_logs'],
        'CML' => [CmlLog::class, 'no_doc', 'cml_logs'],
    ];

    /** Planner-sheet tab per document type, the same tabs the log pages write their status to. */
    private const SHEET_TABS = ['WO' => 'DJA', 'DMI' => 'DJA DMI', 'NSRDI' => 'DJA NSRD'];

    public function __construct(private ?GoogleSheetsSyncService $sheets = null, private ?UnplannedSheetWriter $unplanned = null) {}

    public function plan(LeaderReportImport $import): array
    {
        foreach ($import->rows()->get() as $row) {
            $this->replanRow($row);
        }

        return $this->stats($import);
    }

    /** Decides one row again (after the person changed its category in the preview). */
    public function replanRow(LeaderReportRow $row): void
    {
        [$outcome, $note, $target, $extras] = $this->evaluate($row);
        $row->update([
            'outcome' => $outcome,
            'note' => $note,
            'target_table' => $target?->getTable(),
            'target_id' => $target?->getKey(),
        ] + $extras);
    }

    /**
     * decide() plus the category: WO / DMI that are not in the DJA are CBM or Line Maintenance, NSRDI is CBM or
     * PAINTING. A Line Maintenance document is not saved at all (it is not cabin work).
     *
     * @return array{0: string, 1: ?string, 2: ?Model, 3: array}
     */
    private function evaluate(LeaderReportRow $row): array
    {
        [$outcome, $note, $target] = $this->decide($row);
        $extras = $this->categoryGuess($row, $outcome);
        $category = $extras['category'] ?? $row->category;

        if ($category === 'LINE' && in_array($row->doc_type, ['WO', 'DMI'], true) && in_array($outcome, ['unplanned_new', 'unplanned_update'], true)) {
            return ['line_maintenance', 'Terbaca sebagai pekerjaan line maintenance, bukan CBM. Ubah ke CBM bila keliru.', null, $extras];
        }

        return [$outcome, $note, $target, $extras];
    }

    public function apply(LeaderReportImport $import): array
    {
        DB::transaction(function () use ($import) {
            foreach ($import->rows()->get() as $row) {
                // Re-decide at apply time: the logs may have changed since the preview
                [$outcome, $note, $target, $extras] = $this->evaluate($row);

                if (! in_array($outcome, ['skipped', 'line_maintenance'], true)) {
                    $row->forceFill($extras);   // a category picked in the preview is kept
                    $target = $this->write($row, $outcome, $target, $import);
                }
                $row->update([
                    'outcome' => $outcome,
                    'note' => $note,
                    'target_table' => $target?->getTable(),
                    'target_id' => $target?->getKey(),
                ]);
            }
            $import->update(['status' => 'applied', 'applied_at' => now(), 'stats' => $this->stats($import)]);
        });

        $extra = array_filter([
            'sheet_failed' => $this->pushClosedToSheet($import),
            'unplanned_sheet_failed' => ($unplanned = $this->pushUnplannedToSheet($import))['failed'],
            'unplanned_sheet_written' => $unplanned['written'],
        ]);
        if ($extra) {
            $import->update(['stats' => $import->fresh()->stats + $extra]);
        }

        return $import->fresh()->stats;
    }

    /**
     * Category guess for documents that are not in the DJA; never overwrites one already set (e.g. picked in the preview).
     *   NSRDI    CBM / PAINTING (paint, PPO, exterior placard)
     *   WO, DMI  CBM / LINE, from the ATA chapter and keywords like the DJA sync; a doubtful one stays CBM and is flagged
     */
    private function categoryGuess(LeaderReportRow $row, string $outcome): array
    {
        if (! in_array($outcome, ['unplanned_new', 'unplanned_update'], true) || $row->category) {
            return [];
        }
        $classifier = app(DjaClassifier::class);

        if ($row->doc_type === 'NSRDI') {
            $guess = $classifier->nsrdiCategory($row->description);

            return $guess['category'] ? ['category' => $guess['category'], 'category_check' => $guess['check']] : [];
        }

        if (in_array($row->doc_type, ['WO', 'DMI'], true)) {
            if (! $row->ata && trim((string) $row->description) === '') {
                return ['category' => 'CBM', 'category_check' => true];   // nothing to judge by
            }
            $memory = app(ClassificationMemory::class)->lookup(strtolower($row->doc_type), null, $row->description);
            if ($memory && $memory['category'] !== ClassificationMemory::CONFLICT) {
                return ['category' => $memory['category'], 'category_check' => false];
            }
            $result = $classifier->classify(strtolower($row->doc_type), $row->ata, null, $row->description);

            return match ($result['action']) {
                DjaClassifier::ACCEPT => ['category' => 'CBM', 'category_check' => false],
                DjaClassifier::REJECT => ['category' => 'LINE', 'category_check' => false],
                default => ['category' => 'CBM', 'category_check' => true],
            };
        }

        return [];
    }

    /** Appends / updates unplanned documents in the planner workbook. @return array{written: int, failed: int} */
    private function pushUnplannedToSheet(LeaderReportImport $import): array
    {
        $result = ['written' => 0, 'failed' => 0];
        $writer = $this->unplanned ?? app(UnplannedSheetWriter::class);

        foreach ($import->rows()->whereIn('outcome', ['unplanned_new', 'unplanned_update'])->whereIn('doc_type', ['WO', 'DMI', 'NSRDI'])->get() as $row) {
            $ok = $writer->upsert($row->doc_type, $row->toArray());
            if ($ok === true) {
                $result['written']++;
            } elseif ($ok === false) {
                $result['failed']++;
            }
        }

        return $result;
    }

    /** Writes the close back to the DJA planner sheet like the log pages do. @return int rows whose write-back failed */
    private function pushClosedToSheet(LeaderReportImport $import): int
    {
        if (! $this->sheets) {
            return 0;
        }
        $failed = 0;
        foreach ($import->rows()->where('outcome', 'closed_planned')->get() as $row) {
            [$model] = self::MODELS[$row->doc_type];
            $log = $model::find($row->target_id);
            $dja = $log?->dja_id ? DailyJobAssignment::find($log->dja_id) : null;
            if (! $dja || ! $dja->source_spreadsheet_id) {
                continue;
            }
            try {
                if (! $this->sheets->pushSync($dja->source_spreadsheet_id, self::SHEET_TABS[$row->doc_type], $dja->task_id, 'Closed', null, null)) {
                    $failed++;
                }
            } catch (\Throwable $e) {
                Log::warning('Leader report: gagal menulis ke sheet DJA: '.$e->getMessage());
                $failed++;
            }
        }

        return $failed;
    }

    public function stats(LeaderReportImport $import): array
    {
        $counts = $import->rows()->selectRaw('outcome, count(*) as n')->groupBy('outcome')->pluck('n', 'outcome')->all();

        return ['total' => array_sum($counts)] + $counts;
    }

    /** @return array{0: string, 1: ?string, 2: ?Model} outcome, note, matching log */
    private function decide(LeaderReportRow $row): array
    {
        if (! $row->doc_type || ! isset(self::MODELS[$row->doc_type])) {
            return ['skipped', 'Jenis dokumen tidak dikenali (bukan WO / NSRDI / DMI / CML).', null];
        }
        if (! $row->doc_no) {
            return ['skipped', 'Nomor dokumen kosong.', null];
        }

        [$model, $column] = self::MODELS[$row->doc_type];

        if ($row->doc_type === 'CML') {
            $existing = $model::where($column, $row->doc_no)->first();
            if (! $existing && ! $row->aircraft_registration) {
                return ['skipped', 'Registrasi pesawat kosong.', null];
            }

            return [$existing ? 'cml_update' : 'cml_new', null, $existing];
        }

        $matches = $model::where($column, $row->doc_no)->orderByDesc('id')->get();
        $planned = $matches->whereNotNull('dja_id')->sortBy(fn ($l) => strcasecmp((string) $l->status, 'Closed') === 0 ? 1 : 0)->first();

        if ($planned) {
            $alreadyClosed = strcasecmp((string) $planned->status, 'Closed') === 0;
            if ($row->status === 'Closed' && ! $alreadyClosed) {
                return ['closed_planned', null, $planned];
            }
            $note = $row->status === 'Open' && ! $alreadyClosed && ! $this->hasValidReason($row)
                ? 'Open tanpa reason + code yang valid: status DJA tidak diubah.' : null;

            return ['updated_planned', $note, $planned];
        }

        $unplanned = $matches->whereNull('dja_id')->first();
        if ($unplanned) {
            return ['unplanned_update', null, $unplanned];
        }
        if (! $row->aircraft_registration) {
            return ['skipped', 'Registrasi pesawat kosong.', null];
        }

        return ['unplanned_new', null, null];
    }

    private function write(LeaderReportRow $row, string $outcome, ?Model $target, LeaderReportImport $import): Model
    {
        [$model, $column] = self::MODELS[$row->doc_type];

        $measure = array_filter([
            'man_power' => $row->man_power,
            'man_hour' => $row->man_hour,
            'start_at' => $row->start_at?->toDateTimeString(),
            'end_at' => $row->end_at?->toDateTimeString(),
        ], fn ($v) => $v !== null);
        $measure['leader_import_id'] = $import->id;

        $log = match ($outcome) {
            'closed_planned' => $this->closePlanned($target, $row, $measure),
            'updated_planned' => $this->updatePlanned($target, $row, $measure),
            'unplanned_update', 'cml_update' => $this->updateExisting($target, $row, $measure),
            default => $this->create($model, $column, $row, $measure),
        };

        // who did the job: the many-to-many between jobs and people
        if ($row->crew) {
            app(CrewWriter::class)->replace(
                ['WO' => 'wo_log', 'DMI' => 'dmi_log', 'NSRDI' => 'nsrdi_log', 'CML' => 'cml_log'][$row->doc_type],
                $log->id, $row->crew, $row->man_hour, $row->work_date?->toDateString() ?? now()->toDateString(), $row->station
            );
        }

        return $log;
    }

    private function closePlanned(Model $log, LeaderReportRow $row, array $measure): Model
    {
        $log->fill($measure + ['status' => 'Closed', 'hold_reason_category' => null, 'hold_remarks' => null]);
        if ($log instanceof NsrdiLog) {
            $log->close_date = $row->work_date;
        }
        $log->save();

        return $log;
    }

    private function updatePlanned(Model $log, LeaderReportRow $row, array $measure): Model
    {
        $log->fill($measure);
        // Open with both a reason and a code is a valid status change for a DJA task
        if ($row->status === 'Open' && strcasecmp((string) $log->status, 'Closed') !== 0 && $this->hasValidReason($row)) {
            $log->status = 'Open';
            $log->fill($this->openReason($log, $row));
        }
        $log->save();

        return $log;
    }

    private function updateExisting(Model $log, LeaderReportRow $row, array $measure): Model
    {
        $log->fill($measure + array_filter([
            'status' => $row->status,
            'description' => $row->description,
            'act_station' => $row->doc_type === 'CML' ? null : $row->station,
            'station' => $row->doc_type === 'CML' ? $row->station : null,
        ], fn ($v) => $v !== null));
        if ($log instanceof NsrdiLog) {
            if ($row->status === 'Closed') {
                $log->close_date ??= $row->work_date;
            }
            $log->category = $log->category ?: $row->category ?: app(DjaClassifier::class)->inferNsrdiCategory($log->description);
        }
        $log->save();

        return $log;
    }

    private function create(string $model, string $column, LeaderReportRow $row, array $measure): Model
    {
        $status = $row->status ?? 'Closed';
        $base = [
            'aircraft_registration' => $row->aircraft_registration,
            'description' => $row->description,
            'status' => $status,
            $column => $row->doc_no,
        ];

        $specific = match ($row->doc_type) {
            'WO' => ['date' => $row->work_date, 'act_station' => $row->station, 'plan_station' => $row->plan_station, 'operator' => $row->operator, 'dja_id' => null, 'is_submitted' => false],
            'DMI' => ['date' => $row->work_date, 'act_station' => $row->station, 'plan_station' => $row->plan_station, 'dja_id' => null, 'is_submitted' => false],
            'NSRDI' => ['category' => $row->category ?: app(DjaClassifier::class)->inferNsrdiCategory($row->description), 'refresh_date' => $row->work_date, 'close_date' => $status === 'Closed' ? $row->work_date : null, 'act_station' => $row->station, 'plan_station' => $row->plan_station, 'aoc' => $row->operator, 'dja_id' => null, 'is_submitted' => false, 'import_source' => 'unplanned'],
            default => ['date' => $row->work_date, 'station' => $row->station, 'operator' => $row->operator, 'doc_type' => 'CML', 'dja_id' => null],
        };

        $log = new $model;
        $log->forceFill($base + $specific + $measure);
        if ($status === 'Open') {
            $log->forceFill($this->openReason($log, $row));
        }
        $log->save();

        return $log;
    }

    /** Same columns the Open dialog fills (hold_reason_category + hold_remarks). */
    private function hasValidReason(LeaderReportRow $row): bool
    {
        return $row->reason_open && strlen(trim($row->reason_open)) >= 3
            && in_array(strtoupper(trim((string) $row->code_open)), self::REASON_CODES, true);
    }

    private function openReason(Model $log, LeaderReportRow $row): array
    {
        return array_filter(['hold_remarks' => $row->reason_open, 'hold_reason_category' => $row->code_open ? strtoupper(trim($row->code_open)) : null], fn ($v) => $v !== null);
    }
}
