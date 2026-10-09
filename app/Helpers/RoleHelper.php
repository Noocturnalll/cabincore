<?php

namespace App\Helpers;

/**
 * The roles of the system. There are five divisions, each with its own PIC: CBM, Painting, AIEC, Supporting and
 * Finishing, plus the Manager, an Admin and the Super Admin. What each role may open is decided by the permission
 * matrix in RegistryPermissionSeeder (menu.* permissions), not here.
 */
class RoleHelper
{
    public const SUPER_ADMIN = 'Super Admin';

    public const MANAGER = 'Manager';

    public const ADMIN_CGK = 'Admin CGK';

    public const PIC_CBM = 'PIC CBM';

    public const PIC_PAINTING = 'PIC Painting';

    public const PIC_AIEC = 'PIC AIEC';

    public const PIC_SUPPORTING = 'PIC Supporting';

    public const PIC_FINISHING = 'PIC Finishing';

    /** Old names, kept so existing code and data keep working: Cabin is CBM, AIC is AIEC, Irreg is part of CBM. */
    public const PIC_CABIN = self::PIC_CBM;

    public const PIC_AIC = self::PIC_AIEC;

    public const PIC_IRREG = self::PIC_CBM;

    /** All PIC roles, one per division */
    public const ALL_PIC = [
        self::PIC_CBM,
        self::PIC_PAINTING,
        self::PIC_AIEC,
        self::PIC_SUPPORTING,
        self::PIC_FINISHING,
    ];

    /** Roles that can see audit trail */
    public const AUDIT_TRAIL_ROLES = [self::SUPER_ADMIN, self::MANAGER];

    /** Roles that can see verification queue */
    public const VERIFICATION_ROLES = [self::ADMIN_CGK, ...self::ALL_PIC];

    /** Roles that can see aircraft history */
    public const AIRCRAFT_HISTORY_ROLES = [self::MANAGER, ...self::ALL_PIC];

    /** Middleware string for all PIC roles */
    public static function picMiddleware(): string
    {
        return implode('|', self::ALL_PIC);
    }
}
