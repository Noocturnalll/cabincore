<?php

namespace App\Console\Commands;

use App\Helpers\RoleHelper;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\User;
use App\Models\WoLog;
use App\Notifications\SystemNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('dja:notify-missing-reasons')]
#[Description('Remind stations about Open logs that still have no reason code and remarks (6 AM to 6 PM)')]
class NotifyMissingReasons extends Command
{
    /** True when none of the given columns holds a value. */
    private function blank(Builder $query, array $columns): Builder
    {
        return $query->where(function (Builder $q) use ($columns) {
            foreach ($columns as $column) {
                $q->where(fn (Builder $c) => $c->whereNull($column)->orWhere($column, ''));
            }
        });
    }

    /**
     * An Open log is missing its reason when it has neither a code nor remarks. A reason entered in CBM
     * (hold_*) or already present in the planner sheet (code_open / reason_open / remarks) both count.
     */
    private function missing(string $model, array $codeColumns, array $remarksColumns, string $dateSql, string $activeDate): Builder
    {
        return $model::where('is_submitted', false)
            ->where('status', '!=', 'Closed')
            ->whereRaw("{$dateSql} <= ?", [$activeDate])
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $b) => $this->blank($b, $codeColumns))
                ->orWhere(fn (Builder $b) => $this->blank($b, $remarksColumns)));
    }

    public function handle()
    {
        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');

        $missing = collect();
        $add = function (string $type, $logs, string $numberColumn) use ($missing) {
            foreach ($logs as $log) {
                $missing->push([
                    'type' => $type,
                    'number' => $log->{$numberColumn},
                    'station' => strtoupper(trim($log->act_station ?? $log->plan_station ?? 'UNKNOWN')),
                ]);
            }
        };

        $add('WO', $this->missing(WoLog::class, ['hold_reason_category', 'code_open'], ['hold_remarks', 'reason_open'], 'DATE(date)', $activeDate)->get(), 'wo_number');
        $add('DMI', $this->missing(DmiLog::class, ['hold_reason_category'], ['hold_remarks', 'remarks'], 'DATE(date)', $activeDate)->get(), 'dmi_number');
        $add('NSRDI', $this->missing(NsrdiLog::class, ['hold_reason_category', 'code_open'], ['hold_remarks', 'reason_open'], 'DATE(COALESCE(plan_date, report_date))', $activeDate)->get(), 'nsrdi_number');

        // One reminder per station instead of one message per item per user
        $supervisors = User::whereHas('roles', fn ($q) => $q->whereIn('name', [RoleHelper::SUPER_ADMIN, RoleHelper::MANAGER]))->where('status', 'active')->get();

        foreach ($missing->groupBy('station') as $station => $items) {
            $recipients = User::where('status', 'active')->where('station', $station)->get()
                ->merge($supervisors)->unique('id');

            $counts = $items->groupBy('type')->map->count()->map(fn ($n, $type) => "{$n} {$type}")->implode(', ');
            $message = "Tim {$station}: {$counts} masih Open tanpa code reason / remarks. Mohon dilengkapi sesuai kondisi aktual.";

            foreach ($recipients as $user) {
                $user->notify(new SystemNotification([
                    'type' => 'warning',
                    'title' => 'Alasan Open belum lengkap: '.$station,
                    'message' => $message,
                ]));
            }
        }

        $this->info('Reminders sent for '.$missing->count().' logs in '.$missing->pluck('station')->unique()->count().' stations.');
    }
}
