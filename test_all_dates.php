<?php

use App\Models\WoLog;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$q = WoLog::whereNotNull('dja_id')->where('is_submitted', true)->pluck('date')->unique()->toArray();
var_dump($q);

$q2 = WoLog::whereNull('dja_id')->where('is_submitted', true)->pluck('date')->unique()->toArray();
var_dump($q2);
