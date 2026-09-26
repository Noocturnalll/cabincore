<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            ['name' => 'Manager', 'status' => 'Aktif'],
            ['name' => 'Supervisor', 'status' => 'Aktif'],
            ['name' => 'Staff', 'status' => 'Aktif'],
            ['name' => 'Technician', 'status' => 'Aktif'],
            ['name' => 'Admin', 'status' => 'Aktif'],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(['name' => $position['name']], $position);
        }
    }
}
