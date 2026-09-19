<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', \App\Livewire\Dashboard::class)->middleware(['auth'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', \App\Livewire\Profile\Index::class)->name('profile.index');

    // Master Data
    Route::get('/master/airports', \App\Livewire\Master\Airports::class)->name('master.airports');
    Route::get('/master/aircraft', \App\Livewire\Master\Aircraft::class)->name('master.aircraft');
    Route::get('/master/categories', \App\Livewire\Master\Categories::class)->name('master.categories');

    // Operational
    Route::get('/verification/queue', \App\Livewire\Verification\Queue::class)->name('verification.queue');
    Route::get('/shift/recap', \App\Livewire\Shift\Recap::class)->name('shift.recap');
    Route::get('/tools/equipment', \App\Livewire\Tools\Equipment::class)->name('tools.equipment');
    
    // Modules
    Route::get('/modules/dja', \App\Livewire\Modules\DailyJobAssigment\Index::class)->name('modules.dja');
    Route::get('/modules/daily-report', \App\Livewire\Modules\DailyReport\Index::class)->name('modules.daily-report');
    Route::get('/modules/cml', \App\Livewire\Modules\CmlLog\Index::class)->name('modules.cml');
    Route::get('/modules/nsrdi', \App\Livewire\Modules\NsrdiLog\Index::class)->name('modules.nsrdi');
    Route::get('/modules/dmi', \App\Livewire\Modules\DmiLog\Index::class)->name('modules.dmi');
    Route::get('/modules/wo', \App\Livewire\Modules\WoLog\Index::class)->name('modules.wo');
    Route::get('/modules/ict', \App\Livewire\Modules\IctFinding\Index::class)->name('modules.ict');

    // Analitik
    Route::get('/reports/executive', \App\Livewire\Reports\Executive::class)->name('reports.executive');
    Route::get('/aircraft/history', \App\Livewire\Aircraft\History::class)->name('aircraft.history');

    // Sistem & Keamanan
    Route::get('/users', \App\Livewire\Users\Index::class)->name('users.index');
    Route::get('/audit', \App\Livewire\AuditTrail\Index::class)->name('audit.index');

    // Lainnya
    Route::get('/documents', \App\Livewire\Documents\Center::class)->name('documents.index');
    Route::get('/notifications', \App\Livewire\Notifications\Index::class)->name('notifications.index');
    Route::get('/import/data-center', \App\Livewire\Import\DataCenter::class)->name('import.data-center');
    Route::get('/force-password-reset', \App\Livewire\Auth\ForcePasswordReset::class)->name('force-password-reset');
});

require __DIR__.'/auth.php';
