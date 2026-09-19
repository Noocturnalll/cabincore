<?php

namespace App\Helpers;

class RoleHelper
{
    public const SUPER_ADMIN = 'Super Admin';

    public const MANAGER = 'Manager';

    public const PIC_CABIN = 'PIC Cabin';

    public const PIC_AIC = 'PIC AIC';

    public const PIC_PAINTING = 'PIC Painting';

    public const PIC_SUPPORTING = 'PIC Supporting';

    public const ADMIN_CGK = 'Admin CGK';

    /** All PIC variants */
    public const ALL_PIC = [
        self::PIC_CABIN,
        self::PIC_AIC,
        self::PIC_PAINTING,
        self::PIC_SUPPORTING,
    ];

    /** Roles that can see audit trail */
    public const AUDIT_TRAIL_ROLES = [self::SUPER_ADMIN, self::MANAGER];

    /** Roles that can see verification queue */
    public const VERIFICATION_ROLES = [self::ADMIN_CGK, self::PIC_CABIN, self::PIC_AIC, self::PIC_PAINTING, self::PIC_SUPPORTING];

    /** Roles that can see aircraft history */
    public const AIRCRAFT_HISTORY_ROLES = [self::MANAGER, self::PIC_CABIN, self::PIC_AIC, self::PIC_PAINTING, self::PIC_SUPPORTING];

    /** Middleware string for all PIC roles */
    public static function picMiddleware(): string
    {
        return implode('|', self::ALL_PIC);
    }
}
