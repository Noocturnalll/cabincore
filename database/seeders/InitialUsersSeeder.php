<?php

namespace Database\Seeders;

use App\Livewire\Users\Index as UsersIndex;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The first working accounts: the five PICs, the Manager, the COD and the Super Admin.
 *   php artisan db:seed --class=InitialUsersSeeder
 * Every account starts with the default password (hashed) and is sent to the change-password form at the first
 * login (is_default_password). Safe to run again: an existing ID is left alone, so nobody's own password is reset.
 */
class InitialUsersSeeder extends Seeder
{
    /** ID => [name, role, division, position] */
    private const PEOPLE = [
        '83119910' => ['Haris Afeni', 'PIC Finishing', 'Finishing', 'PIC Finishing'],
        '83099398' => ['Ahmad Fathurohman', 'PIC Supporting', 'Supporting', 'PIC Supporting'],
        '145340' => ['Nasrudin', 'PIC Painting', 'Painting', 'PIC Painting'],
        '83040043' => ['Muhammad Rokib', 'PIC CBM', 'Cabin', 'PIC Cabin Maintenance'],
        '53030503' => ["Nu'man Erianda", 'Manager', 'Cabin', 'General Manager'],
        '83075251' => ['Jaka Suripto', 'PIC AIEC', 'AIEC', 'PIC Aircraft Interior Cleaning'],
        '221927' => ['Chairul Anwar', 'COD', 'Cabin', 'Cabin On Duty'],
        // the system owner: master data (airports, aircraft, categories) and Users are Super Admin only
        '212223' => ['Chairul Anwar', 'Super Admin', 'Cabin', 'System Administrator'],
    ];

    /** Ahmad Fathurohman is also the Cabin On Duty */
    private const EXTRA_ROLES = ['83099398' => ['COD']];

    public function run(): void
    {
        // roles, permissions and the five divisions must exist first
        $this->call(RegistryPermissionSeeder::class);

        $hash = Hash::make(UsersIndex::DEFAULT_PASSWORD);

        foreach (self::PEOPLE as $nik => [$name, $role, $division, $position]) {
            $divisionId = Division::where('name', $division)->value('id');
            $employee = Employee::where('nik', $nik)->first();

            $user = User::firstOrCreate(['nik' => $nik], [
                'name' => $name,
                'email' => $nik.'@bat.local',
                'password' => $hash,
                'is_default_password' => true,
                'status' => 'active',
                'station' => $employee->station ?? 'CGK',
                'division_id' => $divisionId,
                'position_id' => Position::firstOrCreate(['name' => $position])->id,
            ]);

            $user->syncRoles(array_merge([$role], self::EXTRA_ROLES[$nik] ?? []));

            // the HR record follows the division the person leads
            if ($employee && $employee->division_id !== $divisionId) {
                $employee->update(['division_id' => $divisionId]);
            }
        }
    }
}
