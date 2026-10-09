<?php

namespace Database\Seeders;

use App\Livewire\Users\Index;
use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Define all roles
        $roles = [
            'Super Admin',
            'Manager',
            'PIC CBM',
            'PIC AIEC',
            'PIC Painting',
            'PIC Supporting',
            'PIC Finishing',
            'Admin CGK',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->call([
            DivisionSeeder::class,
            PositionSeeder::class,
            AirportSeeder::class,
        ]);

        // Helper to get ids
        $getDivId = fn ($name) => Division::firstOrCreate(['name' => $name])->id;
        $getPosId = fn ($name) => Position::firstOrCreate(['name' => $name])->id;

        $defaultPassword = (string) env('SEED_DEFAULT_PASSWORD', Index::DEFAULT_PASSWORD);
        $defaultPasswordHash = Hash::make($defaultPassword);

        // ── Super Admin ──────────────────────────────────
        $superAdmin = User::firstOrCreate(
            ['nik' => '000000'],
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@bat.local',
                'password' => $defaultPasswordHash,
                'position_id' => $getPosId('System Administrator'),
                'division_id' => $getDivId('IT'),
                'station' => 'CGK',
                'status' => 'active',
                'is_default_password' => true,
            ]
        );
        $superAdmin->syncRoles('Super Admin');

        // ── Manager ──────────────────────────────────────
        $manager = User::firstOrCreate(
            ['nik' => '100001'],
            [
                'name' => 'Budi Santoso',
                'email' => 'manager@bat.local',
                'password' => $defaultPasswordHash,
                'position_id' => $getPosId('Manager Cabin Maintenance'),
                'division_id' => $getDivId('Cabin'),
                'station' => 'CGK',
                'status' => 'active',
                'is_default_password' => true,
            ]
        );
        $manager->syncRoles('Manager');

        // ── PIC Cabin ─────────────────────────────────────
        $picCabin = User::firstOrCreate(
            ['nik' => '200001'],
            [
                'name' => 'Dewi Rahayu',
                'email' => 'pic.cabin@bat.local',
                'password' => $defaultPasswordHash,
                'position_id' => $getPosId('PIC Cabin Maintenance'),
                'division_id' => $getDivId('Cabin'),
                'station' => 'CGK',
                'status' => 'active',
                'is_default_password' => true,
            ]
        );
        $picCabin->syncRoles('PIC CBM');

        // ── PIC AIC ───────────────────────────────────────
        $picAic = User::firstOrCreate(
            ['nik' => '200002'],
            [
                'name' => 'Rizki Pratama',
                'email' => 'pic.aic@bat.local',
                'password' => $defaultPasswordHash,
                'position_id' => $getPosId('PIC Aircraft Interior Cleaning'),
                'division_id' => $getDivId('AIC'),
                'station' => 'CGK',
                'status' => 'active',
                'is_default_password' => true,
            ]
        );
        $picAic->syncRoles('PIC AIEC');

        // ── PIC Painting ──────────────────────────────────
        $picPainting = User::firstOrCreate(
            ['nik' => '200003'],
            [
                'name' => 'Ahmad Fauzi',
                'email' => 'pic.painting@bat.local',
                'password' => $defaultPasswordHash,
                'position_id' => $getPosId('PIC Painting'),
                'division_id' => $getDivId('Painting'),
                'station' => 'CGK',
                'status' => 'active',
                'is_default_password' => true,
            ]
        );
        $picPainting->syncRoles('PIC Painting');

        // ── PIC Supporting ────────────────────────────────
        $picSupporting = User::firstOrCreate(
            ['nik' => '200004'],
            [
                'name' => 'Sari Indah',
                'email' => 'pic.supporting@bat.local',
                'password' => $defaultPasswordHash,
                'position_id' => $getPosId('PIC Supporting'),
                'division_id' => $getDivId('Supporting'),
                'station' => 'CGK',
                'status' => 'active',
                'is_default_password' => true,
            ]
        );
        $picSupporting->syncRoles('PIC Supporting');

        // ── Admin CGK (5 admin, beda divisi) ─────────────
        $admins = [
            ['nik' => '300001', 'name' => 'Hendra Wijaya',    'divisi' => 'Cabin',     'jabatan' => 'Admin Daily Report Cabin'],
            ['nik' => '300002', 'name' => 'Maya Putri',       'divisi' => 'Painting',  'jabatan' => 'Admin Painting'],
            ['nik' => '300003', 'name' => 'Doni Kurniawan',   'divisi' => 'AIC',       'jabatan' => 'Admin Daily Report AIC'],
            ['nik' => '300004', 'name' => 'Fitri Handayani',  'divisi' => 'ICT',       'jabatan' => 'Admin Finding ICT'],
            ['nik' => '300005', 'name' => 'Reza Maulana',     'divisi' => 'IFE',       'jabatan' => 'Admin IFE & Part Number'],
        ];

        foreach ($admins as $adminData) {
            $admin = User::firstOrCreate(
                ['nik' => $adminData['nik']],
                [
                    'name' => $adminData['name'],
                    'email' => strtolower(str_replace(' ', '.', $adminData['name'])).'@bat.local',
                    'password' => $defaultPasswordHash,
                    'position_id' => $getPosId($adminData['jabatan']),
                    'division_id' => $getDivId($adminData['divisi']),
                    'station' => 'CGK',
                    'status' => 'active',
                    'is_default_password' => true,
                ]
            );
            $admin->syncRoles('Admin CGK');
        }
    }
}
