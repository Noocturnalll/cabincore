<?php

namespace Tests\Feature;

use App\Console\Commands\NotifyExpiringTrainings;
use App\Helpers\RoleHelper;
use App\Models\EmployeeTraining;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Kpi\AttentionList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingExpiryAlertTest extends TestCase
{
    use RefreshDatabase;

    private function training(string $nik, string $course, ?string $expiry, string $status = 'valid'): EmployeeTraining
    {
        return EmployeeTraining::create([
            'category' => 'GENERAL', 'employee_nik' => $nik, 'employee_name' => "Nama {$nik}", 'station' => 'CGK', 'course_name' => $course,
            'expiry_date' => $expiry, 'status' => $status,
        ]);
    }

    public function test_status_follows_the_end_date_as_days_pass(): void
    {
        $a = $this->training('1', 'AVSEC', today()->subDay()->toDateString(), 'valid');           // already past
        $b = $this->training('2', 'SMS', today()->addDays(20)->toDateString(), 'valid');          // soon
        $c = $this->training('3', 'CRM', today()->addMonths(6)->toDateString(), 'expiring_soon'); // renewed
        $d = $this->training('4', 'FIRE', null, 'scheduled');                                     // untouched

        NotifyExpiringTrainings::refreshStatuses();

        $this->assertSame('expired', $a->fresh()->status);
        $this->assertSame('expiring_soon', $b->fresh()->status);
        $this->assertSame('valid', $c->fresh()->status);
        $this->assertSame('scheduled', $d->fresh()->status);
    }

    public function test_hr_is_warned_on_the_threshold_days_only_and_once_a_day(): void
    {
        Notification::fake();
        $hr = User::factory()->create(['status' => 'active']);
        $hr->assignRole(Role::findOrCreate(RoleHelper::ADMIN_HC));
        $other = User::factory()->create(['status' => 'active']);

        $this->training('10', 'AVSEC', today()->addDays(30)->toDateString());
        $this->training('11', 'SMS', today()->addDays(45)->toDateString());   // not a threshold day

        $this->artisan('training:notify-expiring')->assertSuccessful();
        Notification::assertSentTo($hr, SystemNotification::class, function ($n) use ($hr) {
            $data = $n->toArray($hr);

            return str_contains(json_encode($data), '30 hari lagi') && str_contains(json_encode($data), 'AVSEC') && ! str_contains(json_encode($data), 'SMS');
        });
        Notification::assertNotSentTo($other, SystemNotification::class);

        // a second run the same day does not send again
        $this->artisan('training:notify-expiring')->assertSuccessful();
        Notification::assertSentToTimes($hr, SystemNotification::class, 1);
    }

    public function test_dashboard_alert_counts_by_date_not_by_stale_status(): void
    {
        $this->training('20', 'AVSEC', today()->subDays(3)->toDateString(), 'valid');      // stale status, really expired
        $this->training('21', 'SMS', today()->addDays(10)->toDateString(), 'valid');       // stale status, really expiring

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole(Role::findOrCreate(RoleHelper::SUPER_ADMIN));

        $items = collect(app(AttentionList::class)->build($user, null));
        $expired = $items->first(fn ($i) => str_contains($i['label'] ?? $i['title'] ?? '', 'kedaluwarsa (Expired)'));
        $soon = $items->first(fn ($i) => str_contains($i['label'] ?? $i['title'] ?? '', 'mendekati'));

        $this->assertSame(1, $expired['count'] ?? null);
        $this->assertSame(1, $soon['count'] ?? null);
    }
}
