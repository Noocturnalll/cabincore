<?php

return [
    /** Effective productive hours per technician per working day, used when a shift has no entry below. */
    'effective_hours' => 10,

    /**
     * Effective hours per shift (a 12-hour shift less breaks and handover), as the AIEC workbook calculates it:
     * 8 for the day shifts and 10 for the night shift.
     */
    'effective_hours_by_shift' => ['PAGI' => 8, 'SIANG' => 8, 'MALAM' => 10],

    /** A single job longer than this is treated as a data-entry slip: its hours are left out instead of inflating the totals. */
    'max_job_hours' => 14,

    /** Roster teams that count as technicians in the capacity (PI and COD are inspectors / controllers). */
    'capacity_teams' => ['CBM', 'AIEC', 'PAINTING', 'IRREG', 'FINISHING'],

    /** Reporting week starts on Thursday (meeting day) and ends on Wednesday. Carbon: 0 = Sunday ... 4 = Thursday. */
    'week_starts_on' => 4,

    /** The NSRDI pivot week starts on Friday: the DJA of 2 Oct is followed through 8 Oct (Carbon: 5 = Friday). */
    'nsrdi_week_starts_on' => 5,

    /** Achievement colours: green at or above target, yellow when short by up to this percent, red beyond it. */
    'yellow_gap_percent' => 10,
];
