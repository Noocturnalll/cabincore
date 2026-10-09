<?php

namespace App\Console\Commands;

use App\Services\Sources\DataSources;
use Illuminate\Console\Command;

class SyncSources extends Command
{
    protected $signature = 'sources:sync {key? : one source (compliance, cbm_closing, amm, targets); omit for every one} {--every= : only sources scheduled hourly or daily}';

    protected $description = 'Syncs the result and reference Google Sheets listed in config/sources.php';

    public function handle(DataSources $sources): int
    {
        @ini_set('memory_limit', '1G');

        $failed = 0;
        foreach ($sources->all() as $key => $s) {
            if (! $s['syncable'] || ($this->argument('key') && $this->argument('key') !== $key)) {
                continue;
            }
            if ($this->option('every') && ($s['every'] ?? null) !== $this->option('every')) {
                continue;
            }
            $result = $sources->run($key);
            $failed += $result['ok'] ? 0 : 1;
            $this->line(($result['ok'] ? '[ok]    ' : '[GAGAL] ')."{$key}: {$result['message']}");
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
