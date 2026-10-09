<?php

namespace App\Livewire\Modules\AircraftCleaning;

class Exterior extends CleaningLog
{
    protected function type(): string
    {
        return 'DCE';
    }

    protected function title(): string
    {
        return 'Exterior Cleaning (DCE)';
    }

    protected function subtitle(): string
    {
        return 'Log dan laporan Deep Cleaning Exterior pesawat';
    }

    protected function teamLabel(): string
    {
        return 'Area Cuci / Shift';
    }

    protected function accent(): string
    {
        return 'purple';
    }
}
