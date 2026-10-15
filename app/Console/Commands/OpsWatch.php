<?php

namespace App\Console\Commands;

use App\Helpers\RoleHelper;
use App\Models\SyncSetting;
use App\Services\Ims\ImsNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The system tells people when it stops working, instead of waiting for somebody to open Sumber Data:
 *   - a core sync (DJA, AC movement) failed or has not run for too long during working hours;
 *   - the Daily Report archive has not run (its marker is written by dailyreport:auto-submit).
 * Each problem alerts Super Admin and the COD desk once every three hours.
 */
class OpsWatch extends Command
{
    protected $signature = 'ops:watch {--dry-run : Print the problems, send nothing}';

    protected $description = 'Alert Super Admin and COD when a sync or the Daily Report archive has stopped';

    public const ARCHIVE_MARKER = 'ops:archive:last_run';

    /** key => minutes without a successful run that counts as stale (the schedule runs every 5 / 15 minutes) */
    private const MAX_AGE = [SyncSetting::Dja => 20, SyncSetting::AcMovement => 30];

    /** @return array<string, string> problem key => message */
    public function issues(?\DateTimeInterface $now = null): array
    {
        $now = $now ? Carbon::instance($now) : now();
        $working = $now->between($now->copy()->setTime(5, 0), $now->copy()->setTime(23, 0));
        $issues = [];

        foreach (self::MAX_AGE as $key => $minutes) {
            $row = SyncSetting::where('key', $key)->first();
            if (! $row) {
                continue;
            }
            if ($row->last_status === 'failed') {
                $issues["sync-failed-$key"] = "Sinkron {$key} gagal: ".Str::limit((string) $row->last_message, 140);
            } elseif ($working && $row->last_synced_at && $row->last_synced_at->lt($now->copy()->subMinutes($minutes))) {
                $issues["sync-stale-$key"] = "Sinkron {$key} terakhir berhasil ".$row->last_synced_at->diffForHumans($now).' (batas '.$minutes.' menit).';
            }
        }

        if ($working) {
            $last = Cache::get(self::ARCHIVE_MARKER);
            $lastRun = $last ? Carbon::parse($last) : null;
            if (! $lastRun || $lastRun->lt($now->copy()->subHours(2))) {
                $issues['archive-stopped'] = 'Arsip Daily Report belum berjalan '.($lastRun ? 'sejak '.$lastRun->diffForHumans($now) : '(belum pernah tercatat)').'. Cek scheduler.';
            }
        }

        return $issues;
    }

    public function handle(ImsNotifier $notifier): int
    {
        $issues = $this->issues();
        if (! $issues) {
            $this->info('Semua normal.');

            return self::SUCCESS;
        }

        foreach ($issues as $key => $message) {
            $this->warn($message);
            if ($this->option('dry-run') || ! Cache::add("ops:alert:$key", 1, now()->addHours(3))) {
                continue;
            }
            foreach ([RoleHelper::SUPER_ADMIN, ...RoleHelper::COD_DESK] as $role) {
                $notifier->toRole($role, 'Peringatan sistem', $message, 'error');
            }
        }

        return self::SUCCESS;
    }
}
