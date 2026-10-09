<?php

namespace App\Livewire\Modules\AircraftCleaning;

class Transit extends CleaningLog
{
    protected function type(): string
    {
        return 'Transit';
    }

    protected function title(): string
    {
        return 'Transit Cleaning';
    }

    protected function subtitle(): string
    {
        return 'Log dan laporan pembersihan pesawat saat transit';
    }
}
