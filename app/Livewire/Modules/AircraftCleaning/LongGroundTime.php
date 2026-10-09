<?php

namespace App\Livewire\Modules\AircraftCleaning;

class LongGroundTime extends CleaningLog
{
    protected function type(): string
    {
        return 'LGT';
    }

    protected function title(): string
    {
        return 'Long Ground Time (LGT)';
    }

    protected function subtitle(): string
    {
        return 'Log dan laporan pembersihan pesawat saat Long Ground Time';
    }
}
