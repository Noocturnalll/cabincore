<?php

namespace App\Livewire\Modules\AircraftCleaning;

class DailyBeautification extends CleaningLog
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
        return 'Log dan laporan Daily Beautification Interior pesawat';
    }
}
