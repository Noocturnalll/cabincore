<?php

use App\Helpers\RoleHelper;
use App\Livewire\Aircraft\History;
use App\Livewire\Auth\ForcePasswordReset;
use App\Livewire\Dashboard;
use App\Livewire\Documents\Center;
use App\Livewire\Import\DataCenter;
use App\Livewire\Master\Aircraft;
use App\Livewire\Master\Airports;
use App\Livewire\Master\Categories;
use App\Livewire\Master\Divisions;
use App\Livewire\Master\Positions;
use App\Livewire\Modules\AircraftCleaning\Exterior;
use App\Livewire\Modules\AircraftCleaning\General;
use App\Livewire\Modules\AircraftCleaning\Interior;
use App\Livewire\Profile\Index;
use App\Livewire\Reports\Executive;
use App\Livewire\Reports\Summary;
use App\Livewire\Shift\Recap;
use App\Livewire\Tools\Equipment;
use App\Livewire\Verification\Queue;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', Dashboard::class)->middleware(['auth'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', Index::class)->name('profile.index');

    // Master Data
    Route::get('/master/airports', Airports::class)->name('master.airports');
    Route::get('/master/aircraft', Aircraft::class)->name('master.aircraft');
    Route::get('/master/categories', Categories::class)->name('master.categories');

    Route::middleware(['role:'.RoleHelper::SUPER_ADMIN])->group(function () {
        Route::get('/master/divisions', Divisions::class)->name('master.divisions');
        Route::get('/master/positions', Positions::class)->name('master.positions');
    });

    // Operational
    Route::get('/verification/queue', Queue::class)->name('verification.queue');
    Route::get('/shift/recap', Recap::class)->name('shift.recap');
    Route::get('/tools/equipment', Equipment::class)->name('tools.equipment');

    // Modules
    Route::get('/modules/dja', App\Livewire\Modules\DailyJobAssigment\Index::class)->name('modules.dja');
    Route::get('/modules/daily-report', App\Livewire\Modules\DailyReport\Index::class)->name('modules.daily-report');
    Route::get('/modules/cml', App\Livewire\Modules\CmlLog\Index::class)->name('modules.cml');
    Route::get('/modules/nsrdi', App\Livewire\Modules\NsrdiLog\Index::class)->name('modules.nsrdi');
    Route::get('/modules/dmi', App\Livewire\Modules\DmiLog\Index::class)->name('modules.dmi');
    Route::get('/modules/wo', App\Livewire\Modules\WoLog\Index::class)->name('modules.wo');
    Route::get('/modules/ict', App\Livewire\Modules\IctFinding\Index::class)->name('modules.ict');
    Route::get('/modules/aircraft-cleaning/general', General::class)->name('modules.cleaning.general');
    Route::get('/modules/aircraft-cleaning/interior', Interior::class)->name('modules.cleaning.interior');
    Route::get('/modules/aircraft-cleaning/exterior', Exterior::class)->name('modules.cleaning.exterior');
    Route::get('/modules/aircraft-cleaning/daily-report', \App\Livewire\Modules\AircraftCleaning\DailyReport::class)->name('modules.cleaning.daily-report');

    // Analitik
    Route::get('/reports/summary', Summary::class)->name('reports.summary');
    Route::get('/reports/executive', Executive::class)->name('reports.executive');
    Route::get('/aircraft/history', History::class)->name('aircraft.history');

    // Sistem & Keamanan
    Route::get('/users', App\Livewire\Users\Index::class)->name('users.index');
    Route::get('/audit', App\Livewire\AuditTrail\Index::class)->name('audit.index');

    // Lainnya
    Route::get('/documents', Center::class)->name('documents.index');
    Route::get('/notifications', App\Livewire\Notifications\Index::class)->name('notifications.index');
    Route::get('/import/data-center', DataCenter::class)->name('import.data-center');
    Route::get('/force-password-reset', ForcePasswordReset::class)->name('force-password-reset');
});

require __DIR__.'/auth.php';
