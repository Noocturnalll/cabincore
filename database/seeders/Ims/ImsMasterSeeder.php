<?php

namespace Database\Seeders\Ims;

use Illuminate\Database\Seeder;
use App\Models\Ims\Category;
use App\Models\Ims\Unit;
use App\Models\Ims\Location;
use App\Models\Ims\AircraftType;

class ImsMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $categories = [
            ['code' => 'CAT-01', 'name' => 'Avionics', 'ata_chapter' => '22'],
            ['code' => 'CAT-02', 'name' => 'Hydraulic', 'ata_chapter' => '29'],
            ['code' => 'CAT-03', 'name' => 'Landing Gear', 'ata_chapter' => '32'],
            ['code' => 'CAT-04', 'name' => 'Engine', 'ata_chapter' => '72'],
            ['code' => 'CAT-05', 'name' => 'Electrical', 'ata_chapter' => '24'],
            ['code' => 'CAT-06', 'name' => 'Pneumatic', 'ata_chapter' => '36'],
            ['code' => 'CAT-07', 'name' => 'Consumable', 'ata_chapter' => null],
            ['code' => 'CAT-08', 'name' => 'Tools', 'ata_chapter' => null],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['code' => $cat['code']], $cat);
        }

        // Units
        $units = ['PCS', 'EA', 'SET', 'LTR', 'KG', 'MTR'];
        foreach ($units as $unit) {
            Unit::firstOrCreate(['code' => $unit], ['name' => $unit]);
        }

        // Locations
        $wh = Location::firstOrCreate(['code' => 'WH-A'], ['name' => 'Warehouse A', 'type' => 'warehouse']);
        $rack = Location::firstOrCreate(['code' => 'WH-A-R01'], ['name' => 'Rack 01', 'type' => 'rack', 'parent_id' => $wh->id]);
        Location::firstOrCreate(['code' => 'WH-A-R01-S01'], ['name' => 'Shelf 01', 'type' => 'shelf', 'parent_id' => $rack->id]);
        
        Location::firstOrCreate(['code' => 'WH-B'], ['name' => 'Warehouse B', 'type' => 'warehouse']);
        Location::firstOrCreate(['code' => 'REPAIR-AREA'], ['name' => 'Repair Area', 'type' => 'repair_area']);
        Location::firstOrCreate(['code' => 'QUARANTINE'], ['name' => 'Quarantine', 'type' => 'quarantine']);

        // Aircraft Types
        AircraftType::firstOrCreate(['code' => 'B737-800'], ['manufacturer' => 'Boeing', 'model' => '737-800']);
        AircraftType::firstOrCreate(['code' => 'A320-200'], ['manufacturer' => 'Airbus', 'model' => 'A320-200']);
    }
}
