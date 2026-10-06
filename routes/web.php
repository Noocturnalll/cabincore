<?php

use App\Helpers\RoleHelper;
use App\Http\Controllers\ImportController;
use App\Livewire\Aircraft\History;
use App\Livewire\Auth\ForcePasswordReset;
use App\Livewire\Dashboard;
use App\Livewire\Documents\Center;
use App\Livewire\Import\DataCenter;
use App\Livewire\Master\Aircraft;
use App\Livewire\Master\Airports;
use App\Livewire\Master\CapacityConfig;
use App\Livewire\Master\Categories;
use App\Livewire\Master\Divisions;
use App\Livewire\Master\Positions;
use App\Livewire\Master\RonConfig;
use App\Livewire\Modules\AircraftCleaning\DailyReport;
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

Route::get('/dashboard', Dashboard::class)->middleware(['auth', 'force.password.reset'])->name('dashboard');

Route::middleware(['auth', 'force.password.reset'])->group(function () {
    Route::get('/profile', Index::class)->name('profile.index');

    // Master Data
    Route::get('/master/airports', Airports::class)->name('master.airports');
    Route::get('/master/aircraft', Aircraft::class)->name('master.aircraft');
    Route::get('/master/categories', Categories::class)->name('master.categories');

    Route::middleware(['role:'.RoleHelper::SUPER_ADMIN])->group(function () {
        Route::get('/master/divisions', Divisions::class)->name('master.divisions');
        Route::get('/master/positions', Positions::class)->name('master.positions');
        Route::get('/master/capacity', CapacityConfig::class)->name('master.capacity-config');
        Route::get('/master/ron', RonConfig::class)->name('master.ron-config');
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
    Route::get('/modules/nsrdi-overdue', App\Livewire\Modules\NsrdiOverdue\Index::class)->name('modules.nsrdi-overdue');
    Route::get('/modules/nsrdi-no-spare', App\Livewire\Modules\NsrdiNoSpare\Index::class)->name('modules.nsrdi-no-spare');
    Route::get('/modules/dmi', App\Livewire\Modules\DmiLog\Index::class)->name('modules.dmi');
    Route::get('/modules/wo', App\Livewire\Modules\WoLog\Index::class)->name('modules.wo');
    Route::get('/modules/capacity', App\Livewire\Modules\Capacity\Index::class)->name('modules.capacity');
    Route::get('/modules/ict/pi', App\Livewire\Modules\IctFinding\Index::class)->name('modules.ict-pi');
    Route::get('/modules/ict/tbd', App\Livewire\Modules\IctFindingTbd\Index::class)->name('modules.ict-tbd');
    Route::get('/modules/aircraft-cleaning/general', General::class)->name('modules.cleaning.general');
    Route::get('/modules/aircraft-cleaning/interior', Interior::class)->name('modules.cleaning.interior');
    Route::get('/modules/aircraft-cleaning/exterior', Exterior::class)->name('modules.cleaning.exterior');
    Route::get('/modules/aircraft-cleaning/daily-report', DailyReport::class)->name('modules.cleaning.daily-report');
    Route::get('/modules/aircraft-cleaning/sync', \App\Livewire\Modules\AircraftCleaning\Sync::class)->name('modules.cleaning.sync');
    Route::get('/modules/aircraft-rotation', App\Livewire\Modules\AircraftRotation\Index::class)->name('modules.aircraft-rotation');
    Route::get('/modules/ac-movement', App\Livewire\Modules\AcMovement\Index::class)->name('modules.ac-movement');
    
    // Inventory Management System (IMS)
    require __DIR__.'/ims.php';

    // Analitik
    Route::get('/reports/summary', Summary::class)->name('reports.summary');
    Route::get('/reports/executive', Executive::class)->name('reports.executive');
    Route::get('/aircraft/history', History::class)->name('aircraft.history');

    // Sistem & Keamanan
    Route::get('/users', App\Livewire\Users\Index::class)->name('users.index');
    Route::get('/audit', App\Livewire\AuditTrail\Index::class)->name('audit.index');

    // Lainnya
    Route::get('/documents', Center::class)->name('documents.index');
    Route::get('/documents/master', \App\Livewire\Documents\Master::class)->name('documents.master');
    Route::get('/notifications', App\Livewire\Notifications\Index::class)->name('notifications.index');
    Route::get('/import/data-center', DataCenter::class)->name('import.data-center');
    Route::get('/force-password-reset', ForcePasswordReset::class)->name('force-password-reset');

    Route::post('/import/daily-report', [ImportController::class, 'dailyReport'])->name('import.daily-report');
    Route::post('/import/aircraft-rotation', [ImportController::class, 'aircraftRotation'])->name('import.aircraft-rotation');
});

require __DIR__.'/auth.php';

Route::get('/_upinfo', fn () => ['file_uploads' => ini_get('file_uploads'), 'upload_tmp_dir' => ini_get('upload_tmp_dir'), 'sys_temp_dir' => sys_get_temp_dir(), 'temp_writable' => is_writable(sys_get_temp_dir()), 'upload_max_filesize' => ini_get('upload_max_filesize'), 'post_max_size' => ini_get('post_max_size'), 'php_ini' => php_ini_loaded_file()]);

Route::post('/daily-report/import-raw', [ImportController::class, 'dailyReportRaw'])->name('daily-report.import-raw')->middleware('auth');
