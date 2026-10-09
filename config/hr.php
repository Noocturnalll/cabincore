<?php

/** Expiry warning windows, in months before the end date. Past the end date is always "expired" (red). */
return [
    'contract' => ['yellow' => 2, 'red' => 1],
    'passport' => ['yellow' => 6, 'red' => 3],

    /**
     * Excel import: job title text => division, first match wins (case-insensitive, whole text searched).
     * Titles that match nothing get no division and are listed in the import summary for a person to place.
     */
    'division_rules' => [
        'Team Irreg' => ['IRREG'],
        'Painting' => ['PAINTING', 'PAINTER'],
        'AIEC' => ['AIEC', 'AIC', 'AEC', 'CLEANING', 'STAFF AC'],
        'Supporting' => ['SUPPORTING'],
        'Cabin' => ['CBM', 'CABIN', 'MEKANIK', 'MECHANIC', 'SURVEYOR', 'PEMBINA'],
    ],

    /** Airport pass area codes, longest first so "PU" is not read as "P" + "U". */
    'pasban_codes' => ['PU', 'AD', 'BD', 'A', 'B', 'P'],
];
