<?php

namespace App\Livewire\Modules\DailyBeautification;

use App\Livewire\Modules\AircraftCleaning\CleaningLog;

class Index extends CleaningLog
{
    protected function type(): string
    {
        return 'DBI';
    }

    protected function title(): string
    {
        return 'Daily Beautification (DBI)';
    }

    protected function subtitle(): string
    {
        return 'Log dan laporan Daily Beautification Interior kabin pesawat';
    }

    protected function accent(): string
    {
        return 'blue';
    }

    protected function section(): string
    {
        return 'Cabin Maintenance';
    }
}
