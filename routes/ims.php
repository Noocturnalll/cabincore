<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('ims')->name('ims.')->group(function () {
    // Phase 1: Catalog & Loan
    Route::get('/catalog', \App\Livewire\Modules\Ims\Katalog\Index::class)->name('catalog');
    Route::get('/requests', \App\Livewire\Modules\Ims\Peminjaman\Index::class)->name('requests');
    
    // Phase 2: Approval, Repair, Stock
    Route::get('/approvals', \App\Livewire\Modules\Ims\Approval\Index::class)->name('approvals');
    Route::get('/repairs', \App\Livewire\Modules\Ims\Repair\Index::class)->name('repairs');
    Route::get('/penerimaan', \App\Livewire\Modules\Ims\Penerimaan\Index::class)->name('penerimaan');
    Route::get('/opname', \App\Livewire\Modules\Ims\Opname\Index::class)->name('opname');
    Route::get('/transfer', \App\Livewire\Modules\Ims\Transfer\Index::class)->name('transfer');
    
    // Phase 3: History & Reports
    Route::get('/reports', \App\Livewire\Modules\Ims\Report\Index::class)->name('reports');
    
    // Phase 0/4: Master Data
    Route::get('/master', \App\Livewire\Modules\Ims\Master\Index::class)->name('master');
    Route::get('/master/items', \App\Livewire\Modules\Ims\Master\Items::class)->name('master.items');
    Route::get('/master/categories', \App\Livewire\Modules\Ims\Master\Categories::class)->name('master.categories');
    Route::get('/master/units', \App\Livewire\Modules\Ims\Master\Units::class)->name('master.units');
    Route::get('/master/locations', \App\Livewire\Modules\Ims\Master\Locations::class)->name('master.locations');
    Route::get('/master/suppliers', \App\Livewire\Modules\Ims\Master\Suppliers::class)->name('master.suppliers');
    Route::get('/master/aircraft-types', \App\Livewire\Modules\Ims\Master\AircraftTypes::class)->name('master.aircraft-types');
});
