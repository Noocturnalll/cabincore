<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('sync:daily-dja')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('sync:ac-movement')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('dailyreport:auto-submit')->everyFifteenMinutes();
Schedule::command('dja:notify-missing-reasons')->hourly()->between('6:00', '18:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('report:daily')->dailyAt('10:00');
Schedule::command('app:snapshot-capacity-data')->dailyAt('16:00');
