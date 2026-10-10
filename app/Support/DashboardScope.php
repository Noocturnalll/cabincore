<?php

namespace App\Support;

use App\Helpers\RoleHelper;
use App\Models\User;

/**
 * What the dashboard shows a user: which stations, which modules and which periods.
 * Division leads (the five PICs and the COD desk) follow the work of their division across all stations; the modules
 * are the ones their division actually runs, so a Painting PIC does not get a WO board and the AIEC PIC does not get
 * DJA. Every role may look at the daily, weekly and monthly view.
 */
class DashboardScope
{
    private const ALL_PERIODS = ['daily', 'weekly', 'monthly'];

    public function __construct(
        public ?array $stations,   // null = semua station
        public array $modules,     // modul yang boleh tampil
        public array $periods,     // periode yang boleh dipilih
    ) {}

    public static function for(User $user): self
    {
        return match (true) {
            $user->hasRole(RoleHelper::SUPER_ADMIN) => new self(null, ['dja', 'unplanned', 'cml', 'ict', 'ac', 'ims', 'nsrdi', 'kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::MANAGER) => new self(null, ['dja', 'unplanned', 'cml', 'ict', 'ac', 'ims', 'nsrdi', 'kpi'], self::ALL_PERIODS),
            $user->hasAnyRole(RoleHelper::COD_DESK) => new self(null, ['dja', 'unplanned', 'cml', 'ict', 'ac', 'ims', 'nsrdi', 'kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::ADMIN_CGK) => new self(['CGK'], ['dja', 'unplanned', 'cml', 'ict', 'ac', 'ims', 'nsrdi', 'kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::PIC_CBM) => new self(null, ['dja', 'unplanned', 'cml', 'ict', 'ac', 'ims', 'nsrdi', 'kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::PIC_PAINTING) => new self(null, ['nsrdi', 'unplanned', 'ims', 'kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::PIC_AIEC) => new self(null, ['ac', 'ims', 'kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::PIC_SUPPORTING) => new self(null, ['ims', 'ac', 'kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::PIC_FINISHING) => new self(null, ['ims', 'kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::DEPUTY) => new self(null, ['dja', 'unplanned', 'cml', 'ict', 'ac', 'ims', 'nsrdi', 'kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::ADMIN_ICT) => new self(null, ['ict', 'kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::ADMIN_HC) => new self(null, ['kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::ADMIN_DOCUMENT) => new self(null, ['kpi'], self::ALL_PERIODS),
            $user->hasRole(RoleHelper::ADMIN_AIEC) => new self(null, ['ac', 'ims', 'kpi'], self::ALL_PERIODS),
            default => new self($user->station ? [$user->station] : null, ['dja', 'unplanned', 'ac'], ['daily']),
        };
    }
}
