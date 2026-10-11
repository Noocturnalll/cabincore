<?php

namespace App\Imports;

use App\Services\Dja\DjaRonSyncService;
use Maatwebsite\Excel\Concerns\ToArray;

class DjaRonSheetImport implements ToArray
{
    public function __construct(protected string $tabName = 'List AC') {}

    public function array(array $array): void
    {
        app(DjaRonSyncService::class)->syncFromArray($array, $this->tabName);
    }
}
