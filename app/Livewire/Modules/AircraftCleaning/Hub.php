<?php

namespace App\Livewire\Modules\AircraftCleaning;

use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * One entry in the menu for every kind of cleaning log. The type is a tab; the log underneath is the same page that
 * used to have its own menu item (General, DCI, DCE, DBI, GCI, GCE, LGT, Transit).
 */
class Hub extends Component
{
    public const TYPES = [
        'transit' => [Transit::class, 'Transit'],
        'general' => [General::class, 'General'],
        'dci' => [Interior::class, 'DCI'],
        'dce' => [Exterior::class, 'DCE'],
        'dbi' => [DailyBeautification::class, 'DBI'],
        'gci' => [GeneralInterior::class, 'GCI'],
        'gce' => [GeneralExterior::class, 'GCE'],
        'lgt' => [LongGroundTime::class, 'LGT'],
    ];

    #[Url(as: 'tipe')]
    public string $type = 'general';

    public function setType(string $type): void
    {
        $this->type = array_key_exists($type, self::TYPES) ? $type : 'general';
    }

    public function render()
    {
        $type = array_key_exists($this->type, self::TYPES) ? $this->type : 'general';

        return view('livewire.modules.aircraft-cleaning.hub', [
            'types' => self::TYPES,
            'type' => $type,
            'component' => self::TYPES[$type][0],
        ])->layout('components.layouts.app', ['title' => 'Aircraft Cleaning']);
    }
}
