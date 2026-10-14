<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

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

    public static function detect(?string $description): string
    {
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

        // rows that read like a DBI but are not marked yet
        DB::table('cml_logs')->where('kind', '!=', self::DBI)
            ->where(fn ($q) => $q->whereRaw("UPPER(description) LIKE '%BEAUTIFICATION%'")->orWhereRaw("UPPER(description) LIKE '%DBI%'"))
            ->select('id', 'description')->orderBy('id')
            ->chunkById(500, function ($rows) use (&$changed) {
                $ids = $rows->filter(fn ($r) => self::detect($r->description) === self::DBI)->pluck('id')->all();
                if ($ids) {
                    $changed += DB::table('cml_logs')->whereIn('id', $ids)->update(['kind' => self::DBI]);
                }
            });

        // rows marked DBI whose description does not say so (an earlier rule, or an edited description)
        DB::table('cml_logs')->where('kind', self::DBI)->select('id', 'description')->orderBy('id')
            ->chunkById(500, function ($rows) use (&$changed) {
                $ids = $rows->filter(fn ($r) => self::detect($r->description) !== self::DBI)->pluck('id')->all();
                if ($ids) {
                    $changed += DB::table('cml_logs')->whereIn('id', $ids)->update(['kind' => self::CML]);
                }
            });

        return $changed;
    }
}
