<?php

namespace App\Console\Commands;

use App\Helpers\RoleHelper;
use App\Models\EmployeeTraining;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

/**
 * Daily: keeps the training status in step with the calendar and warns HR when a certificate is about to expire.
 * A warning goes out when a record is exactly 60, 30, 14 or 7 days from its end date, on the day it ends, and the day
 * after (expired), so nobody is nagged every morning for the same record.
 */
class NotifyExpiringTrainings extends Command
{
    protected $signature = 'training:notify-expiring {--force : send again even if today was already sent}';

    protected $description = 'Refresh training status by expiry date and notify HR about certificates close to expiring';

    /** days left => label */
    private const STEPS = [60 => '60 hari lagi', 30 => '30 hari lagi', 14 => '14 hari lagi', 7 => '7 hari lagi', 0 => 'berakhir hari ini', -1 => 'baru kedaluwarsa kemarin'];

    public function handle(): int
    {
        $refreshed = self::refreshStatuses();

        $key = 'training-notify:'.today()->toDateString();
        if (! $this->option('force') && ! Cache::add($key, 1, now()->addDay())) {
            $this->info("Status diperbarui: {$refreshed}. Pengingat hari ini sudah dikirim.");

            return self::SUCCESS;
        }

        $lines = [];
        $total = 0;
        foreach (self::STEPS as $days => $label) {
            $date = today()->addDays($days)->toDateString();
            $rows = EmployeeTraining::whereDate('expiry_date', $date)->orderBy('employee_name')->get(['employee_name', 'course_name', 'station']);
            if ($rows->isEmpty()) {
                continue;
            }
            $total += $rows->count();
            $names = $rows->take(5)->map(fn ($r) => "{$r->employee_name} ({$r->course_name})")->implode('; ');
            $more = $rows->count() > 5 ? ' dan '.($rows->count() - 5).' lainnya' : '';
            $lines[] = "{$label}: {$rows->count()} sertifikat - {$names}{$more}";
        }

        if ($total === 0) {
            $this->info("Status diperbarui: {$refreshed}. Tidak ada sertifikat pada ambang peringatan hari ini.");

            return self::SUCCESS;
        }

        // only roles that exist (a missing role would make the lookup throw)
        $roles = Role::whereIn('name', [RoleHelper::ADMIN_HC, RoleHelper::MANAGER, RoleHelper::SUPER_ADMIN])->pluck('name')->all();
        $recipients = $roles ? User::role($roles)->where('status', 'active')->get()->unique('id') : collect();
        foreach ($recipients as $user) {
            $user->notify(new SystemNotification([
                'type' => 'warning',
                'title' => 'Training mendekati kedaluwarsa',
                'message' => implode("\n", $lines),
            ]));
        }

        $this->info("Status diperbarui: {$refreshed}. Pengingat {$total} sertifikat dikirim ke {$recipients->count()} penerima.");

        return self::SUCCESS;
    }

    /**
     * valid / expiring_soon / expired follow the end date; scheduled, unscheduled and rows without an end date are left.
     *
     * @return int rows whose status changed
     */
    public static function refreshStatuses(): int
    {
        $today = today()->toDateString();
        $soon = today()->addMonthsNoOverflow(2)->toDateString();
        $changed = 0;

        $changed += EmployeeTraining::whereNotNull('expiry_date')->whereIn('status', ['valid', 'expiring_soon'])
            ->whereDate('expiry_date', '<', $today)->update(['status' => 'expired']);
        $changed += EmployeeTraining::whereNotNull('expiry_date')->whereIn('status', ['valid', 'expired'])
            ->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $soon)->update(['status' => 'expiring_soon']);
        $changed += EmployeeTraining::whereNotNull('expiry_date')->whereIn('status', ['expiring_soon', 'expired'])
            ->whereDate('expiry_date', '>', $soon)->update(['status' => 'valid']);

        return $changed;
    }
}
