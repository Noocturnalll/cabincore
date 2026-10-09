<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES = [
        RoleHelper::SUPER_ADMIN, RoleHelper::MANAGER, RoleHelper::ADMIN_CGK,
        RoleHelper::PIC_CBM, RoleHelper::PIC_PAINTING, RoleHelper::PIC_AIEC, RoleHelper::PIC_SUPPORTING, RoleHelper::PIC_FINISHING, RoleHelper::COD,
    ];

    /** url => roles allowed to open it (everything else must be 403) */
    private function matrix(): array
    {
        $pics = RoleHelper::ALL_PIC;
        $all = self::ROLES;
        $sa = RoleHelper::SUPER_ADMIN;
        $mg = RoleHelper::MANAGER;
        $ad = RoleHelper::ADMIN_CGK;

        return [
            '/modules/wo' => [$sa, $mg, $ad, RoleHelper::PIC_CBM, RoleHelper::COD],
            '/modules/dja' => [$sa, $mg, $ad, RoleHelper::PIC_CBM, RoleHelper::COD],
            '/modules/nsrdi' => [$sa, $mg, $ad, RoleHelper::PIC_CBM, RoleHelper::COD, RoleHelper::PIC_PAINTING],
            '/modules/nsrdi-overdue' => [$sa, $mg, $ad, RoleHelper::PIC_CBM, RoleHelper::COD, RoleHelper::PIC_PAINTING],
            '/modules/daily-report' => [$sa, $mg, $ad, RoleHelper::PIC_CBM, RoleHelper::COD, RoleHelper::PIC_PAINTING],
            '/modules/aircraft-cleaning' => [$sa, $mg, $ad, RoleHelper::PIC_AIEC],
            '/modules/aircraft-cleaning/exterior' => [$sa, $mg, $ad, RoleHelper::PIC_AIEC],
            '/modules/ict/pi' => [$sa, $mg, $ad, RoleHelper::PIC_CBM, RoleHelper::COD],
            '/modules/capacity' => [$sa, $mg, $ad, RoleHelper::PIC_CBM, RoleHelper::COD, RoleHelper::PIC_AIEC],
            '/reports/kpi' => $all,
            '/reports/summary' => $all,
            '/reports/executive' => [$sa, $mg],
            '/audit' => [$sa, $mg],
            '/master/airports' => [$sa],
            '/master/aircraft' => [$sa],
            '/master/categories' => [$sa],
            '/tools/equipment' => [$sa, ...$pics],
            '/aircraft/history' => [$sa, $mg, ...$pics],
            '/verification/queue' => [$sa, $ad, ...$pics],
            '/shift/recap' => [$sa, $ad],
            '/ims/catalog' => $all,
        ];
    }

    private function userFor(string $role): User
    {
        $division = Division::where('name', 'Cabin')->first();
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'division_id' => $division?->id]);
        $u->assignRole($role);

        return $u;
    }

    public function test_every_role_only_opens_what_the_matrix_allows(): void
    {
        $this->seed(RegistryPermissionSeeder::class);
        $bad = [];

        foreach (self::ROLES as $role) {
            $this->actingAs($this->userFor($role));
            foreach ($this->matrix() as $url => $allowed) {
                $status = $this->get($url)->status();
                $expectOk = in_array($role, $allowed, true);
                if ($expectOk && $status === 403) {
                    $bad[] = "$role must open $url but got 403";
                } elseif (! $expectOk && $status !== 403) {
                    $bad[] = "$role must NOT open $url but got $status";
                } elseif ($status >= 500) {
                    $bad[] = "$role $url -> $status";
                }
            }
        }

        $this->assertSame([], $bad, implode("\n", $bad));
    }

    public function test_no_page_returns_a_server_error_for_any_role(): void
    {
        $this->seed(RegistryPermissionSeeder::class);
        $skip = '#^(_debugbar|horizon|livewire|sanctum|storage|up|login|register|forgot-password|reset-password|verify-email|confirm-password|force-password-reset)#';
        $urls = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods(), true) && ! str_contains($r->uri(), '{') && ! preg_match($skip, $r->uri()))
            ->map(fn ($r) => '/'.ltrim($r->uri(), '/'))->unique()->values();
        $this->assertGreaterThan(50, $urls->count());

        $errors = [];
        foreach (self::ROLES as $role) {
            $this->actingAs($this->userFor($role));
            foreach ($urls as $url) {
                $status = $this->get($url)->status();
                if ($status >= 500) {
                    $errors[] = "$role $url -> $status";
                }
            }
        }

        $this->assertSame([], $errors, implode("\n", $errors));
    }

    public function test_sidebar_only_lists_the_blocks_of_the_role(): void
    {
        $this->seed(RegistryPermissionSeeder::class);

        $this->actingAs($this->userFor(RoleHelper::PIC_PAINTING))->get('/modules/nsrdi')
            ->assertOk()->assertSee('NSRDI Logs')->assertDontSee('Aircraft Cleaning')->assertDontSee(route('modules.wo'));

        $this->actingAs($this->userFor(RoleHelper::PIC_AIEC))->get('/reports/summary')
            ->assertOk()->assertSee('Aircraft Cleaning')->assertDontSee(route('modules.wo'));

        $this->actingAs($this->userFor(RoleHelper::MANAGER))->get('/reports/summary')
            ->assertOk()->assertSee(route('modules.wo'))->assertSee('Aircraft Cleaning');
    }
}
