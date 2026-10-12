<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DetailDataSheet implements Export, FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    protected Collection $collection;

    protected array $headings;

    protected string $title;

    public function __construct($arg1, array $headings, $arg3 = 'Data Detail')
    {
        if ($arg1 instanceof Collection) {
            $this->collection = $arg1;
            $this->title = is_string($arg3) ? $arg3 : 'Data Detail';
        } else {
            $this->title = is_string($arg1) ? $arg1 : 'Data Detail';
            $this->collection = $arg3 instanceof Collection ? $arg3 : collect($arg3);
        }
        $this->headings = $headings;
    }

    public function collection(): Collection
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }
}
