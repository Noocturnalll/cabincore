<?php

namespace App\Console\Commands;

use App\Models\Ims\Transaction;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

#[Signature('ims:notify-overdue-loans')]
#[Description('Remind the borrower and the approvers about IMS loans on / after their return date (config ims.loan_overdue_notify_days)')]
class NotifyOverdueLoans extends Command
{
    public function handle()
    {
        $offsets = config('ims.loan_overdue_notify_days', [0, 3]);

        $approvers = Permission::where('name', 'ims.approval.act')->exists()
            ? User::permission('ims.approval.act')->where('status', 'active')->get()
            : collect();

        $sent = 0;
        foreach ($offsets as $daysLate) {
            $dueDate = today()->subDays((int) $daysLate)->toDateString();

            $loans = Transaction::with(['items.item', 'requester'])
                ->where('type', 'out')->where('usage_type', 'loan')->where('status', 'approved')
                ->whereDate('expected_return_date', $dueDate)
                ->whereDoesntHave('returns', fn ($q) => $q->where('usage_type', 'loan_return'))
                ->get();

            foreach ($loans as $loan) {
                $when = $daysLate == 0 ? 'jatuh tempo hari ini' : "terlambat {$daysLate} hari";
                $items = $loan->items->map(fn ($l) => $l->qty.'x '.($l->item->name ?? '-'))->implode(', ');
                $message = "Pinjaman {$loan->code} ({$items}) {$when}. Mohon segera dikembalikan.";

                $recipients = collect([$loan->requester])->filter()->merge($approvers)->unique('id');
                foreach ($recipients as $user) {
                    $user->notify(new SystemNotification([
                        'type' => $daysLate == 0 ? 'warning' : 'error',
                        'title' => 'Pinjaman IMS '.($daysLate == 0 ? 'jatuh tempo' : 'terlambat'),
                        'message' => $message,
                    ]));
                    $sent++;
                }
            }
        }

        $this->info("Pengingat pinjaman terkirim: {$sent}");
    }
}
