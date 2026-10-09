<?php

namespace App\Livewire\Modules\AircraftCleaning;

class Interior extends CleaningLog
{
    protected function type(): string
    {
        return 'DCI';
    }

    protected function title(): string
    {
        return 'Interior Cleaning (DCI)';
    }

    protected function subtitle(): string
    {
        return 'Log dan laporan Deep Cleaning Interior pesawat';
    }

    protected function teamLabel(): string
    {
        return 'Tim DCI / Shift';
    }

    protected function accent(): string
    {
        return 'blue';
    }
}
