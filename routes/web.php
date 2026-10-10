<?php

use App\Helpers\RoleHelper;
use App\Http\Controllers\ImportController;
use App\Livewire\Aircraft\History;
use App\Livewire\Auth\ForcePasswordReset;
use App\Livewire\DashboardHub;
use App\Livewire\Documents\Center;
use App\Livewire\Documents\Master;
use App\Livewire\Import\DataCenter;
use App\Livewire\Master\Aircraft;
use App\Livewire\Master\Airports;
use App\Livewire\Master\CapacityConfig;
use App\Livewire\Master\Categories;
use App\Livewire\Master\Divisions;
use App\Livewire\Master\Entries;
use App\Livewire\Master\Positions;
use App\Livewire\Master\RonConfig;
use App\Livewire\Modules\AircraftCleaning\DailyBeautification;
use App\Livewire\Modules\AircraftCleaning\DailyReport;
use App\Livewire\Modules\AircraftCleaning\Exterior;
use App\Livewire\Modules\AircraftCleaning\General;
use App\Livewire\Modules\AircraftCleaning\GeneralExterior;
use App\Livewire\Modules\AircraftCleaning\GeneralInterior;
use App\Livewire\Modules\AircraftCleaning\Hub;
use App\Livewire\Modules\AircraftCleaning\Interior;
use App\Livewire\Modules\AircraftCleaning\LongGroundTime;
use App\Livewire\Modules\AircraftCleaning\Sync;
use App\Livewire\Modules\Hr\Employees;
use App\Livewire\Profile\Index;
use App\Livewire\Reports\DocumentAccuracy;
use App\Livewire\Reports\Executive;
use App\Livewire\Reports\Kpi;
use App\Livewire\Reports\KpiDashboard;
use App\Livewire\Reports\Lgt;
use App\Livewire\Reports\Manpower;
use App\Livewire\Reports\NsrdiPivot;
use App\Livewire\Reports\Summary;
use App\Livewire\Shift\Recap;
use App\Livewire\Tools\Equipment;
use App\Livewire\Verification\Queue;
use App\Models\Document;
use App\Models\Rotation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::redirect('/', '/login');

Route::get('/dashboard', DashboardHub::class)->middleware(['auth', 'force.password.reset'])->name('dashboard');

Route::middleware(['auth', 'force.password.reset'])->group(function () {
    Route::get('/profile', Index::class)->name('profile.index');

    // Master Data
    Route::get('/master/airports', Airports::class)->middleware('role:Super Admin')->name('master.airports');
    Route::get('/master/aircraft', Aircraft::class)->middleware('role:Super Admin')->name('master.aircraft');
    Route::get('/master/categories', Categories::class)->middleware('role:Super Admin')->name('master.categories');

    Route::middleware(['role:'.RoleHelper::SUPER_ADMIN])->group(function () {
        Route::get('/master/divisions', Divisions::class)->name('master.divisions');
        Route::get('/master/positions', Positions::class)->name('master.positions');
    });

    Route::get('/master/capacity', CapacityConfig::class)->middleware('role:'.RoleHelper::SUPER_ADMIN.'|'.RoleHelper::DEPUTY)->name('master.capacity-config');
    Route::get('/master/ron', RonConfig::class)->middleware('role:'.RoleHelper::SUPER_ADMIN.'|'.RoleHelper::COD)->name('master.ron-config');

    // Operational
    Route::get('/verification/queue', Queue::class)->middleware('role:Super Admin|Admin CGK|PIC CBM|PIC Painting|PIC AIEC|PIC Supporting|PIC Finishing')->name('verification.queue');
    Route::get('/shift/recap', Recap::class)->middleware('role:Super Admin|Admin CGK')->name('shift.recap');
    Route::get('/tools/equipment', Equipment::class)->middleware('role:Super Admin|PIC CBM|PIC Painting|PIC AIEC|PIC Supporting|PIC Finishing')->name('tools.equipment');
    Route::get('/hr/employees', Employees::class)->middleware('permission:hr.view')->name('hr.employees');
    Route::get('/registry/{module}', App\Livewire\Modules\Registry\Index::class)->name('registry.index');
    Route::get('/modules/aircraft-cleaning/dbi', DailyBeautification::class)->middleware('permission:menu.cleaning')->name('modules.cleaning.dbi');
    Route::get('/modules/aircraft-cleaning/gci', GeneralInterior::class)->middleware('permission:menu.cleaning')->name('modules.cleaning.gci');
    Route::get('/modules/aircraft-cleaning/gce', GeneralExterior::class)->middleware('permission:menu.cleaning')->name('modules.cleaning.gce');
    Route::get('/modules/aircraft-cleaning/lgt', LongGroundTime::class)->middleware('permission:menu.cleaning')->name('modules.cleaning.lgt');

    // Modules
    Route::get('/modules/dja', App\Livewire\Modules\DailyJobAssigment\Index::class)->middleware('permission:menu.production')->name('modules.dja');
    Route::get('/modules/daily-report', App\Livewire\Modules\DailyReport\Index::class)->middleware('permission:menu.production|menu.painting')->name('modules.daily-report');
    Route::get('/reports/document-accuracy', DocumentAccuracy::class)->middleware('permission:kpi.view')->name('reports.document-accuracy');
    Route::get('/reports/lgt', Lgt::class)->middleware('permission:kpi.view')->name('reports.lgt');
    Route::get('/reports/manpower', Manpower::class)->middleware('permission:kpi.view')->name('reports.manpower');
    Route::get('/master/data/{type}', Entries::class)->name('master.data');
    Route::get('/attendance', App\Livewire\Modules\Attendance\Index::class)->middleware('permission:attendance.view')->name('attendance.index');
    Route::get('/assets', App\Livewire\Modules\Assets\Index::class)->middleware('permission:asset.view')->name('assets.index');
    Route::get('/sources', App\Livewire\Modules\Sources\Index::class)->middleware('permission:sources.view')->name('sources.index');
    Route::get('/modules/aircraft-cleaning', Hub::class)->middleware('permission:menu.cleaning')->name('modules.cleaning.hub');
    Route::get('/modules/lgt', App\Livewire\Modules\Lgt\Index::class)->middleware('permission:lgt.view')->name('modules.lgt');
    Route::get('/reports/nsrdi-pivot', NsrdiPivot::class)->middleware('permission:kpi.view')->name('reports.nsrdi-pivot');
    Route::get('/kpi', KpiDashboard::class)->middleware('permission:kpi.view')->name('kpi.dashboard');
    Route::get('/compliance/daily', App\Livewire\Modules\Compliance\Index::class)->middleware('permission:compliance.view')->name('compliance.daily');
    Route::get('/modules/leader-report', App\Livewire\Modules\LeaderReport\Index::class)->middleware('permission:leader.import')->name('modules.leader-report');
    Route::get('/modules/cml', App\Livewire\Modules\CmlLog\Index::class)->middleware('permission:menu.production')->name('modules.cml');
    Route::get('/modules/nsrdi', App\Livewire\Modules\NsrdiLog\Index::class)->middleware('permission:menu.production|menu.painting')->name('modules.nsrdi');
    Route::get('/modules/nsrdi-overdue', App\Livewire\Modules\NsrdiOverdue\Index::class)->middleware('permission:menu.nsrdi')->name('modules.nsrdi-overdue');
    Route::get('/modules/nsrdi-no-spare', App\Livewire\Modules\NsrdiNoSpare\Index::class)->middleware('permission:menu.nsrdi')->name('modules.nsrdi-no-spare');
    Route::get('/modules/dmi', App\Livewire\Modules\DmiLog\Index::class)->middleware('permission:menu.production')->name('modules.dmi');
    Route::get('/modules/wo', App\Livewire\Modules\WoLog\Index::class)->middleware('permission:menu.production')->name('modules.wo');
    Route::get('/modules/capacity', App\Livewire\Modules\Capacity\Index::class)->middleware('permission:menu.capacity')->name('modules.capacity');
    Route::get('/modules/ict/pi', App\Livewire\Modules\IctFinding\Index::class)->middleware('permission:menu.ict')->name('modules.ict-pi');
    Route::get('/modules/ict/tbd', App\Livewire\Modules\IctFindingTbd\Index::class)->middleware('permission:menu.ict')->name('modules.ict-tbd');
    Route::get('/modules/aircraft-cleaning/general', General::class)->middleware('permission:menu.cleaning')->name('modules.cleaning.general');
    Route::get('/modules/aircraft-cleaning/interior', Interior::class)->middleware('permission:menu.cleaning')->name('modules.cleaning.interior');
    Route::get('/modules/aircraft-cleaning/exterior', Exterior::class)->middleware('permission:menu.cleaning')->name('modules.cleaning.exterior');
    Route::get('/modules/aircraft-cleaning/daily-report', DailyReport::class)->middleware('permission:menu.cleaning')->name('modules.cleaning.daily-report');
    Route::get('/modules/aircraft-cleaning/sync', Sync::class)->middleware('permission:menu.cleaning')->name('modules.cleaning.sync');
    Route::get('/rotations/{rotation}/view', function (Rotation $rotation) {
        return response(Storage::get($rotation->html_path))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; script-src 'unsafe-inline'");
    })->name('rotations.view');
    Route::get('/rotations/{rotation}/download', function (Rotation $rotation) {
        return Storage::download($rotation->file_path);
    })->name('rotations.download');

    Route::get('/modules/aircraft-rotation', App\Livewire\Modules\AircraftRotation\Index::class)->middleware('permission:menu.capacity')->name('modules.aircraft-rotation');
    Route::get('/modules/ac-movement', App\Livewire\Modules\AcMovement\Index::class)->middleware('permission:menu.capacity')->name('modules.ac-movement');

    // Inventory Management System (IMS)
    require __DIR__.'/ims.php';

    // Analitik
    Route::get('/reports/summary', Summary::class)->middleware('permission:menu.analytics')->name('reports.summary');
    Route::get('/reports/kpi', Kpi::class)->middleware('permission:menu.analytics')->name('reports.kpi');
    Route::get('/reports/executive', Executive::class)->middleware('role:Super Admin|Manager')->name('reports.executive');
    Route::get('/aircraft/history', History::class)->middleware('role:Super Admin|Manager|PIC CBM|PIC Painting|PIC AIEC|PIC Supporting|PIC Finishing')->name('aircraft.history');

    // Sistem & Keamanan
    Route::get('/users', App\Livewire\Users\Index::class)->middleware('role:'.RoleHelper::SUPER_ADMIN)->name('users.index');
    Route::get('/roles', App\Livewire\Roles\Index::class)->middleware('role:'.RoleHelper::SUPER_ADMIN)->name('roles.index');
    Route::get('/audit', App\Livewire\AuditTrail\Index::class)->middleware('role:Super Admin|Manager')->name('audit.index');

    // Lainnya
    Route::get('/documents', Center::class)->name('documents.index');
    Route::get('/documents/master', Master::class)->middleware('role:'.RoleHelper::SUPER_ADMIN)->name('documents.master');
    Route::get('/documents/{document}/download', function (Document $document) {
        abort_unless($document->fileExists(), 404, 'File dokumen tidak ditemukan.');

        return Storage::disk('public')->download($document->file_path, $document->downloadFilename());
    })->name('documents.download');
    Route::get('/notifications', App\Livewire\Notifications\Index::class)->name('notifications.index');
    Route::get('/import/data-center', DataCenter::class)->name('import.data-center');
    Route::get('/force-password-reset', ForcePasswordReset::class)->name('force-password-reset');

    Route::post('/import/daily-report', [ImportController::class, 'dailyReport'])->name('import.daily-report');
    Route::post('/import/aircraft-rotation', [ImportController::class, 'aircraftRotation'])->name('import.aircraft-rotation');
});

require __DIR__.'/auth.php';

Route::post('/daily-report/import-raw', [ImportController::class, 'dailyReportRaw'])->name('daily-report.import-raw')->middleware('auth');
