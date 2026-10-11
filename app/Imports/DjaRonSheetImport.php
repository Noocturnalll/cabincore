<?php

namespace App\Imports;

use App\Services\Dja\DjaRonSyncService;
use Maatwebsite\Excel\Concerns\ToArray;

class DjaRonSheetImport implements ToArray
{
    public function __construct(protected string $tabName = 'List AC', protected ?string $unused = null, protected ?string $targetDate = null) {}

    public function array(array $array): void
    {
        app(DjaRonSyncService::class)->syncFromArray($array, $this->tabName, $this->targetDate);
    }
}
