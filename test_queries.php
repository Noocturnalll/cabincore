<?php

use App\Models\WoLog;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$q = WoLog::whereNotNull('dja_id')->where('is_submitted', true)->whereHas('dailyJobAssignment', function ($q) {
    $q->whereDate('date', '<', '2026-09-30')->whereDate('date', '2026-09-18');
});
var_dump($q->count());

$q2 = WoLog::whereNull('dja_id')->where('is_submitted', true)->whereDate('date', '<', '2026-09-30')->whereDate('date', '2026-09-18');
var_dump($q2->count());
