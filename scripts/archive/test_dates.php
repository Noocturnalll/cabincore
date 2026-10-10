<?php

use App\Models\DailyJobAssignment;
use App\Models\WoLog;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$dja = DailyJobAssignment::find(30);
var_dump($dja->date, $dja->getOriginal('date'));

$wo = WoLog::find(5);
var_dump($wo->date, $wo->getOriginal('date'));
