<?php

namespace App\Livewire\Modules\AircraftCleaning;

class GeneralExterior extends CleaningLog
{
    protected function type(): string
    {
        return 'GCE';
    }

    protected function title(): string
    {
        return 'General Cleaning Exterior (GCE)';
    }

    protected function subtitle(): string
    {
        return 'Log dan laporan General Cleaning Exterior pesawat';
    }
}
