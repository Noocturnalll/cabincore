<?php

namespace App\Console\Commands;

use App\Models\DmiLog;
use App\Models\WoLog;
use App\Services\Dja\ClassificationMemory;
use Illuminate\Console\Command;

class SeedClassificationMemory extends Command
{
    protected $signature = 'classification:seed {--dry-run : only count}';

    protected $description = 'Teaches the WO / DMI memory from jobs that already live in the cabin logs (they were accepted as CBM)';

    public function handle(ClassificationMemory $memory): int
    {
        $count = ['wo' => 0, 'dmi' => 0];
        $dry = (bool) $this->option('dry-run');

        foreach (['wo' => WoLog::class, 'dmi' => DmiLog::class] as $kind => $model) {
            // distinct descriptions of jobs that came from the DJA (dja_id) - each already passed the filter or a person
            $model::whereNotNull('dja_id')->whereNotNull('description')->select('description')->distinct()
                ->chunk(500, function ($rows) use ($kind, $memory, $dry, &$count) {
                    foreach ($rows as $row) {
                        [$signature] = ClassificationMemory::signature(null, $row->description);
                        if (! $signature) {
                            continue;
                        }
                        if (! $dry) {
                            // history only fills gaps: it must not outvote a decision a person made later
                            if ($memory->lookup($kind, null, $row->description) === null) {
                                $memory->remember($kind, null, $row->description, ClassificationMemory::CBM);
                            }
                        }
                        $count[$kind]++;
                    }
                });
        }

        $this->info(($dry ? '[DRY RUN] ' : '')."Pola CBM dipelajari: WO {$count['wo']}, DMI {$count['dmi']}.");

        return self::SUCCESS;
    }
}
