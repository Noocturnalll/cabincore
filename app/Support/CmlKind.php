<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A line in the CML log is either a normal CML or a DBI (Daily Beautification Inspection). DBI belongs to Cabin
 * Maintenance and has its own tab: a CML whose DESCRIPTION says "DBI" / "beautification inspection" is a DBI.
 * The word DBI in the station cell is only the label of a block of the sheet (not a station): the station is
 * cleared, and the line stays a CML unless its description says DBI.
 */
class CmlKind
{
    public const CML = 'CML';

    public const DBI = 'DBI';

    /** A line is DBI when its TASK TYPE says DBI, or its description does. */
    public static function detect(?string $description, ?string $taskType = null): string
    {
        if (strtoupper(trim((string) $taskType)) === 'DBI') {
            return self::DBI;
        }

        return preg_match('/\bDBI\b|BEAUTIFICATION/i', (string) $description) ? self::DBI : self::CML;
    }

    /**
     * Classify what was written without going through the model (bulk imports, old rows). Idempotent and cheap:
     * only rows that mention DBI, or are marked DBI, are looked at.
     *
     * @return int rows whose kind changed
     */
    public static function sync(): int
    {
        // "DBI" typed into the station column is a label, not a station
        DB::table('cml_logs')->whereRaw("UPPER(TRIM(station)) = 'DBI'")->update(['station' => null]);

        $changed = 0;
        // older databases (the migration that added `kind` runs before task_type exists) have no task_type yet
        $hasTask = Schema::hasColumn('cml_logs', 'task_type');
        $cols = $hasTask ? ['id', 'description', 'task_type'] : ['id', 'description'];

        // rows that read like a DBI but are not marked yet
        DB::table('cml_logs')->where('kind', '!=', self::DBI)
            ->where(function ($q) use ($hasTask) {
                $q->whereRaw("UPPER(description) LIKE '%BEAUTIFICATION%'")->orWhereRaw("UPPER(description) LIKE '%DBI%'");
                if ($hasTask) {
                    $q->orWhereRaw("UPPER(TRIM(task_type)) = 'DBI'");
                }
            })
            ->select($cols)->orderBy('id')
            ->chunkById(500, function ($rows) use (&$changed) {
                $ids = $rows->filter(fn ($r) => self::detect($r->description, $r->task_type ?? null) === self::DBI)->pluck('id')->all();
                if ($ids) {
                    $changed += DB::table('cml_logs')->whereIn('id', $ids)->update(['kind' => self::DBI]);
                }
            });

        // rows marked DBI whose description does not say so (an earlier rule, or an edited description)
        $marked = DB::table('cml_logs')->where('kind', self::DBI);
        if ($hasTask) {
            $marked->where(fn ($q) => $q->whereNull('task_type')->orWhereRaw("UPPER(TRIM(task_type)) != 'DBI'"));
        }
        $marked->select('id', 'description')->orderBy('id')
            ->chunkById(500, function ($rows) use (&$changed) {
                $ids = $rows->filter(fn ($r) => self::detect($r->description) !== self::DBI)->pluck('id')->all();
                if ($ids) {
                    $changed += DB::table('cml_logs')->whereIn('id', $ids)->update(['kind' => self::CML]);
                }
            });

        return $changed;
    }
}
