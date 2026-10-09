<?php

namespace App\Livewire\Modules\AircraftCleaning;

class GeneralInterior extends CleaningLog
{
    protected function type(): string
    {
        return 'GCI';
    }

    protected function title(): string
    {
        return 'General Cleaning Interior (GCI)';
    }

    protected function subtitle(): string
    {
        return 'Log dan laporan General Cleaning Interior pesawat';
    }
}
