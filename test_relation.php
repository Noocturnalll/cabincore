<?php

use App\Models\WoLog;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$wologs = WoLog::whereNotNull('dja_id')->where('is_submitted', true)->where('date', '2026-09-18')->with('dailyJobAssignment')->get();
foreach ($wologs as $wo) {
    if (! $wo->dailyJobAssignment) {
        echo "WoLog {$wo->id} has dja_id {$wo->dja_id} but DJA is missing!\n";
    } else {
        echo "WoLog {$wo->id} date: {$wo->date}, DJA date: {$wo->dailyJobAssignment->date}\n";
    }
}
