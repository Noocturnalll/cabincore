<?php

namespace App\Console\Commands;

use App\Services\Dja\DailyReportArchiver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('dailyreport:auto-submit')]
#[Description('Moves finished WO / DMI / NSRDI logs past the 18:00 cutoff into the Daily Report')]
class AutoSubmitDailyReport extends Command
{
    public function handle(DailyReportArchiver $archiver)
    {
        $moved = $archiver->run();
        $held = $archiver->held();

        $this->info("Masuk Daily Report - WO: {$moved['wo']}, DMI: {$moved['dmi']}, NSRDI: {$moved['nsrdi']}");
        $this->line("Tertahan (Open tanpa code reason / remarks) - WO: {$held['wo']}, DMI: {$held['dmi']}, NSRDI: {$held['nsrdi']}");
    }
}
