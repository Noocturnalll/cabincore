<?php

namespace Database\Seeders;

use App\Models\MasterEntry;
use App\Services\Master\MasterSettings;
use Illuminate\Database\Seeder;

/**
 * Fills Data Master with the defaults of config/master.php. Only adds what is missing: a value somebody has already
 * changed is never overwritten, so this is safe to run after every deploy.
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('master.types') as $type => $def) {
            $order = 0;
            foreach ($def['defaults'] ?? [] as $code => [$label, $attrs]) {
                MasterEntry::firstOrCreate(
                    ['type' => $type, 'code' => $code],
                    ['label' => $label, 'attrs' => $attrs, 'sort_order' => ++$order, 'is_active' => true]
                );
            }
        }
        MasterSettings::flush();
    }
}
