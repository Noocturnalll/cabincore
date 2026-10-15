<?php

namespace App\Services\Dja;

use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\SheetSyncLog;
use App\Models\WoLog;
use App\Services\Leader\UnplannedSheetWriter;
use Illuminate\Database\Eloquent\Model;

/**
 * Web -> Google Sheet for unplanned work. Never throws and never shows an error: when the sheet cannot be written
 * (e.g. edit access closes at 10:00) the attempt is kept in sheet_sync_logs and retried later; the log row itself is
 * unaffected and still reaches the Daily Report bank at 18:00.
 */
class UnplannedSheetPusher
{
    private const TYPE = [WoLog::class => 'WO', DmiLog::class => 'DMI', NsrdiLog::class => 'NSRDI'];

    public function __construct(private ?UnplannedSheetWriter $writer = null)
    {
        $this->writer ??= app(UnplannedSheetWriter::class);
    }

    /** @return bool|null true written, false failed (logged), null nothing to do */
    public function push(Model $log): ?bool
    {
        $type = self::TYPE[$log::class] ?? null;
        if (! $type || $log->dja_id) {
            return null; // planned work is written back by the DJA sync
        }

        return $this->send($type, $this->row($type, $log), $log);
    }

    /** Retries failed writes; resolved entries are marked healed. */
    public function retryFailed(int $limit = 50): int
    {
        $healed = 0;
        $entries = SheetSyncLog::where('direction', 'push')->where('event', 'failed')->whereNull('resolved_at')
            ->whereNotNull('payload')->where('attempts', '<', 500)->orderBy('id')->limit($limit)->get();

        foreach ($entries as $e) {
            $type = strtoupper((string) $e->kind);
            $payload = $e->payload;
            if (! isset(UnplannedSheetWriter::TABS[$type]) || ! is_array($payload)) {
                continue;
            }
            $ok = $this->writer->upsert($type, $payload);
            if ($ok === true) {
                $e->update(['event' => 'healed', 'resolved_at' => now(), 'attempts' => $e->attempts + 1]);
                $healed++;
            } else {
                $e->increment('attempts');
            }
        }

        return $healed;
    }

    private function send(string $type, array $row, Model $log): ?bool
    {
        try {
            $ok = $this->writer->upsert($type, $row);
        } catch (\Throwable $e) {
            $ok = false;
        }

        if ($ok === false) {
            SheetSyncLog::note('push', 'failed', strtolower($type), $row['doc_no'] ?? null, 'Gagal menulis ke GSS (akses tutup / koneksi). Akan dicoba ulang otomatis.', $row);
        }

        return $ok;
    }

    private function row(string $type, Model $log): array
    {
        $doc = match ($type) {
            'WO' => $log->wo_number,
            'DMI' => $log->dmi_number,
            'NSRDI' => $log->nsrdi_number,
        };

        return [
            'doc_no' => $doc,
            'aircraft_registration' => $log->aircraft_registration,
            'station' => $log->act_station ?: $log->plan_station,
            'plan_station' => $log->plan_station,
            'operator' => $log->operator ?? $log->aoc ?? null,
            'description' => $log->description,
            'status' => $log->status,
            'work_date' => ($log->date ?? $log->plan_date ?? $log->report_date)?->format('Y-m-d'),
            'man_power' => $log->man_power,
            'man_hour' => $log->man_hour,
            'category' => $type === 'NSRDI' ? $log->category : null,
            'wg' => $log->work_group ?? null,
            'ata' => $log->ata ?? null,
            'reason_open' => $log->reason_open ?? $log->hold_remarks,
            'code_open' => $log->code_open ?? $log->hold_reason_category,
            'pn_required' => $log->pn_required ?? null,
            'remarks' => $log->remarks ?? null,
        ];
    }
}
