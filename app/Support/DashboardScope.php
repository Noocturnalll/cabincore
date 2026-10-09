<?php

namespace App\Support;

use App\Helpers\RoleHelper;
use App\Models\User;

class DashboardScope
{
    public function __construct(
        public ?array $stations,   // null = semua station
        public array $modules,     // modul yang boleh tampil
        public array $periods,     // periode yang boleh dipilih
    ) {}

    public static function for(User $user): self
    {
        return match (true) {
            $user->hasRole(RoleHelper::SUPER_ADMIN) => new self(
                null,
                ['dja', 'unplanned', 'cml', 'ict', 'ac', 'ims', 'nsrdi', 'kpi'],
                ['daily', 'weekly', 'monthly'],
            ),
            $user->hasRole(RoleHelper::MANAGER) => new self(
                null,
                ['dja', 'unplanned', 'cml', 'ict', 'ac', 'kpi'],
                ['daily', 'weekly', 'monthly'],
            ),
            $user->hasRole(RoleHelper::ADMIN_CGK) => new self(
                ['CGK'],
                ['dja', 'unplanned', 'cml', 'ac', 'ims'],
                ['daily', 'weekly'],
            ),
            default => new self(
                $user->station ? [$user->station] : null,
                ['dja', 'unplanned', 'ac'],
                ['daily'],
            ),
        };
    }
}
