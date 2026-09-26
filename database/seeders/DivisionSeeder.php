<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            ['name' => 'Cabin', 'status' => 'Aktif'],
            ['name' => 'AIC', 'status' => 'Aktif'],
            ['name' => 'Painting', 'status' => 'Aktif'],
            ['name' => 'Supporting', 'status' => 'Aktif'],
        ];

        foreach ($divisions as $division) {
            Division::updateOrCreate(['name' => $division['name']], $division);
        }
    }
}
