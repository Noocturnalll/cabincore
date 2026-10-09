<?php

namespace App\Livewire\Modules\AircraftCleaning;

class General extends CleaningLog
{
    protected function type(): string
    {
        return 'General';
    }

    protected function title(): string
    {
        return 'General Cleaning';
    }

    protected function subtitle(): string
    {
        return 'Log dan laporan pencucian umum pesawat';
    }
}
