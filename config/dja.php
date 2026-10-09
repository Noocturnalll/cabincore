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
    'ata_accept' => ['11', '23', '25', '33', '35', '38', '44', '52', '56'],

    'wo' => [
        'include' => [
            'LIFE VEST', 'UNDERSEAT', 'INFANT', 'ESCAPE SLIDE', 'OXYGEN', 'OXYGEN MASK', 'MEGAPHONE',
            'FIRE EXTINGUISHER', 'FIRE BOTTLE', 'FIREX', 'PORTABLE FIREX',
            'POTABLE WATER', 'WATER FILTER', 'STERILIZATION', 'WASTE COMPARTMENT', 'VACUUM', 'LAVATORY',
            'PASSENGER CABIN', 'SEAT', 'ROLLER BLIND', 'COMPARTMENT WINDOWS', 'GALLEY',
            'PEST CONTROL', 'PORTABLE EXTINGUISHER', 'EXTINGUISHER', 'MEDICAL KIT', 'FIRST AID', 'LIFEVEST', 'EMERGENCY EQUIPMENT',
            'ESCAPE SLIDE', 'SLIDE RAFT', 'LIFE RAFT', 'EMERGENCY ESCAPE',
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

    // A match together with one of these words means "probably not a cabin job": send to review, do not auto-accept.
    'exclude' => [
        'ENGINE', 'APU', 'LANDING GEAR', 'HYDRAULIC', 'FUEL TANK', 'BORESCOPE', 'PROPELLER', 'AVIONICS', 'FLIGHT CONTROL',
    ],

    // Weak cabin-related words: not enough to accept, but worth a human look.
    'review' => [
        'CABIN', 'PASSENGER', 'PAX', 'CARPET', 'LINING', 'OVERHEAD', 'BIN', 'CURTAIN', 'MIRROR', 'PLACARD', 'DECAL',
        'SIDEWALL', 'PANEL', 'COVER', 'LIGHT', 'DOOR', 'WINDOW', 'TABLE', 'LAV', 'TOILET',
        // Too broad to accept on their own (pitot probe cleaning, landing gear cleaning, IFE cleaning ...): a person looks.
        'CLEANING', 'CHEMICAL',
    ],

    // NSRDI is not keyword based: the planner's own CATEGORY column decides.
    'nsrdi_categories' => ['CBM', 'PAINTING'],

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
