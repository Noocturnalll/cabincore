<?php

namespace App\Imports;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class CalculatedValueBinder extends DefaultValueBinder
{
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value) && str_starts_with($value, '=')) {
            $oldValue = $cell->getOldCalculatedValue();

            if ($oldValue !== null) {
                $cell->setValueExplicit($oldValue, DataType::TYPE_STRING);

                return true;
            }

            try {
                $calcValue = $cell->getCalculatedValue();
                $cell->setValueExplicit($calcValue, DataType::TYPE_STRING);

                return true;
            } catch (\Exception $e) {
                $cell->setValueExplicit('', DataType::TYPE_STRING);

                return true;
            }
        }

        return parent::bindValue($cell, $value);
    }
}
