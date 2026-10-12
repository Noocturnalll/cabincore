<?php

/*
|--------------------------------------------------------------------------
| DJA sync: classification rules
|--------------------------------------------------------------------------
|
| Used by App\Services\Dja\DjaClassifier for every row pulled from the planner
| sheet (Google Sheets sync AND Excel import). Keywords are matched on whole
| words (case-insensitive, punctuation ignored, optional plural "S"/"ES"), so
| "LAV" no longer matches "LAVA" and "LIFE-VEST" matches "LIFE VEST".
|
| Decision order for the WO and DMI tabs:
|   1. A human decision stored in dja_sync_reviews (Terima / Tolak)  -> always wins
|   2. ATA chapter in `ata_reject`                                   -> reject
|   3. ATA chapter in `ata_accept`                                   -> accept
|   4. include keyword AND no exclude keyword                        -> accept
|   5. include keyword AND an exclude keyword (conflict)             -> review
|   6. only a weak keyword                                           -> review
|   7. nothing matches                                               -> reject (kept in the audit list)
|
| Nothing is ever discarded silently: review and reject rows are stored in
| dja_sync_reviews so a planner can overrule the rules with one click.
|
*/

return [
    // Fallback when the sheet row has no planning-station column value.
    'default_station' => env('DJA_DEFAULT_STATION', 'BTH'),

    // Sync is expected every 15 minutes; warn when the last successful run is older than this.
    'stale_after_minutes' => 20,

    // Undecided review/reject rows that have not been seen in a sync for this many days are removed.
    'review_retention_days' => 3,

    // AC Movement is mirrored every 5 minutes; warn when the last successful run is older than this.
    'ac_movement_stale_after_minutes' => 10,

    'ata_reject' => ['72', '32'],
    // Only chapters that are cabin by nature. 23 (comms), 33 (lights), 52 (doors) and 56 (windows) also cover
    // cockpit / exterior work, so they must be backed by a cabin keyword instead of being accepted blindly.
    'ata_accept' => ['11', '25', '35', '38', '44'],

    'wo' => [
        'include' => [
            'LIFE VEST', 'UNDERSEAT', 'INFANT', 'ESCAPE SLIDE', 'OXYGEN', 'OXYGEN MASK', 'MEGAPHONE',
            'FIRE EXTINGUISHER', 'FIRE BOTTLE', 'FIREX', 'PORTABLE FIREX',
            'POTABLE WATER', 'WATER FILTER', 'STERILIZATION', 'WASTE COMPARTMENT', 'VACUUM', 'LAVATORY',
            'PASSENGER CABIN', 'SEAT', 'ROLLER BLIND', 'COMPARTMENT WINDOWS', 'GALLEY',
            'PEST CONTROL', 'PORTABLE EXTINGUISHER', 'EXTINGUISHER', 'MEDICAL KIT', 'FIRST AID', 'LIFEVEST', 'EMERGENCY EQUIPMENT',
            'ESCAPE SLIDE', 'SLIDE RAFT', 'LIFE RAFT', 'EMERGENCY ESCAPE', 'EMERGENCY LIGHT', 'EMERGENCY LIGHTS',
        ],
    ],

    'dmi' => [
        'include' => [
            'WINDOW LIGHT', 'CEILING LIGHT', 'ENTRY LIGHT', 'ILLUMINATE', 'NOT ILL', 'ALWAYS ILLUMINATE',
            'SEAT', 'RECLINE', 'AUTORECLINE', 'UPRIGHT POSITION', 'SEAT BELT', 'SEATBELT', 'TRAY TABLE',
            'FASTEN', 'FASTEN SEAT BELT', 'SMOKING', 'NO SMOKING SIGN',
            'LAV', 'LAVATORY', 'FLUSHING', 'HANDSET', 'CFD',
        ],
    ],

    // Non-cabin work groups from the planner sheet to reject immediately.
    'work_group_exclude' => [
        'AVIONICS', 'AV', 'MECHANIC', 'MECH', 'STRUCTURE', 'STR', 'ENGINE', 'ENG', 'B1', 'B2', 'SHEET METAL', 'ELECTRICAL',
    ],

    // Absolute non-cabin jobs: rejected immediately without conflict review, regardless of ATA or other matches.
    'hard_exclude' => [
        // Cargo
        'CARGO', 'CARGO COMPARTMENT', 'CARGO DOOR', 'CARGO NET', 'CARGO FLOOR', 'CARGO BULKHEAD', 'BAGGAGE COMPARTMENT', 'FWD CARGO', 'AFT CARGO',
        // Cockpit windows / windshields / exterior seals
        'COCKPIT WINDOW', 'COCKPIT WINDOWS', 'FLIGHT COMPARTMENT WINDOW', 'WINDSHIELD', 'HUMP SEAL', 'SEALS FROM OUTSIDE',
        // Airframe systems & landing gear
        'LANDING GEAR', 'MLG', 'NLG', 'PACK AREA', 'AIR CONDITIONING PACK', 'MAIN BATTERY', 'BATTERY AND CHARGER',
        'AC GENERATION', 'OIL SYSTEM', 'DRAIN MAST', 'RADOME', 'PITOT', 'BORESCOPE', 'THRUST REVERSER', 'COWLING', 'NACELLE',
        // APU / Engine bottles
        'APU FIREX', 'ENGINE FIREX', 'APU CARTRIDGE',
        // Exterior cleaning & placards (handled by exterior wash / LM)
        'DEEP CLEANING EXTERIOR', 'EXTERIOR CLEANING', 'EXTERIOR PLACARD', 'WASHING EXTERIOR',
        // Mechanical / ECS / Waste systems non-cabin
        'WATER SEPARATOR', 'INSIDE WASTE TANK', 'WASTE TANK CLEANLINESS',
        // Cockpit / Crew oxygen systems (Line Maintenance / Avionics)
        'CREW OXYGEN', 'FLIGHT CREW OXYGEN', 'CREW OXYGEN MASK', 'CREW OXYGEN CYLINDER', 'HYD CYLINDER',
        // Sensors / Equipment racks
        'TEMPERATURE SENSOR', 'SENSOR FILTER', 'EQUIPMENT RACK',
        // Cockpit overhead P5 / flight deck controls
        'DRIPSHIELD BRACKET', 'P5 PANEL', 'OVERHEAD P5',
        // Structural seat beams (structure shop), crew oxygen regulator masks, hydraulic-tested bottles
        'SEAT BEAMS', 'OXYGEN REGULATOR', 'HYD BOTTLE',
    ],

    // Cockpit emergency equipment items that ARE valid cabin maintenance even when located in cockpit.
    'cockpit_emergency_include' => [
        'LIFE VEST', 'LIFEVEST', 'FLASHLIGHT', 'FLASHLIGHTS', 'PBE', 'PROTECTIVE BREATHING', 'BREATHING EQPT', 'PORTABLE FIREX',
    ],

    // A match together with one of these words means "not a cabin job": reject from CBM sync.
    'exclude' => [
        // Aircraft Systems / Propulsion / Flight Controls
        'ENGINE', 'APU', 'LANDING GEAR', 'HYDRAULIC', 'FUEL TANK', 'BORESCOPE', 'PROPELLER', 'AVIONICS', 'FLIGHT CONTROL',
        // Cockpit / Flight Deck / Pilot
        'COCKPIT', 'FLIGHT DECK', 'PILOT', 'COPILOT', 'CAPT', 'CAPTAIN', 'F/O', 'FIRST OFFICER', 'OBSERVER',
        'PEDESTAL', 'WINDSHIELD', 'RUDDER', 'CONTROL COLUMN', 'SIDESTICK', 'THRUST LEVER', 'CREW SEAT', 'PILOT SEAT', 'OBSERVER SEAT',
        // Airframe / Cargo / Exterior / Ground Equipment
        'CARGO', 'CARGO DOOR', 'BAGGAGE', 'RADOME', 'WING', 'FLAP', 'SLAT', 'SPOILER', 'AILERON', 'ELEVATOR',
        'STABILIZER', 'TAIL', 'FUSELAGE', 'PITOT', 'STATIC PORT', 'BRAKE', 'TIRE', 'WHEEL', 'STRUT',
        'GROUND LOCK', 'GEAR PIN', 'BYPASS PIN', 'ANTENNA', 'PROBE', 'DRAIN MAST', 'NACELLE', 'COWLING', 'THRUST REVERSER', 'EXHAUST',
    ],

    // Weak cabin-related words: not enough to accept on their own, but worth a human look.
    'review' => [
        'CABIN', 'PASSENGER', 'PAX', 'CARPET', 'LINING', 'OVERHEAD', 'BIN', 'CURTAIN', 'MIRROR', 'PLACARD', 'DECAL',
        'SIDEWALL', 'PANEL', 'COVER', 'LIGHT', 'DOOR', 'WINDOW', 'TABLE', 'LAV', 'TOILET',
        // Too broad to accept on their own (pitot probe cleaning, landing gear cleaning, IFE cleaning ...): a person looks.
        'CLEANING', 'CHEMICAL',
    ],

    // NSRDI is not keyword based: the planner's own CATEGORY column decides.
    'nsrdi_categories' => ['CBM', 'PAINTING', 'CABIN', 'INTERIOR', 'CABIN MAINTENANCE', 'AOC', 'NSRDI'],

    // NSRDI that arrives without a category (leader report, unplanned): PAINTING when it is about paint on the
    // aircraft - paint peel off (PPO), paint / cat, livery - or an exterior placard. Anything else is CBM.
    'nsrdi_painting_keywords' => [
        'PAINT', 'PAINTING', 'REPAINT', 'PAINT PEEL', 'PEEL OFF', 'PEELING', 'PPO', 'PENGECATAN',
    ],
    // Probably paint, but the word has other meanings: the category is filled and flagged "perlu dicek".
    'nsrdi_painting_uncertain' => ['CAT', 'TOUCH UP', 'LIVERY', 'FADED', 'LUNTUR', 'KUSAM'],
    'nsrdi_exterior_words' => ['EXTERIOR', 'EXT', 'EXTERNAL', 'OUTSIDE', 'FUSELAGE', 'ENGINE COWL', 'NACELLE'],
    'nsrdi_placard_words' => ['PLACARD', 'DECAL', 'STICKER', 'MARKING', 'LOGO'],
];
