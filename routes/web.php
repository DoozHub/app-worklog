<?php

use Illuminate\Support\Facades\Route;
use Dooz\Worklog\Http\Controllers\WorklogWebController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web', 
    'auth', 
])->prefix('worklog')->name('dooz.worklog.')->group(function () {
    Route::get('/', [WorklogWebController::class, 'index'])->name('index');
    Route::get('/create', [WorklogWebController::class, 'create'])->name('create');
    Route::post('/', [WorklogWebController::class, 'store'])->name('store');
    
    // Clients
    Route::resource('clients', \Dooz\Worklog\Http\Controllers\ClientWebController::class);

    // Projects
    Route::resource('projects', \Dooz\Worklog\Http\Controllers\ProjectWebController::class);

    // Reports
    Route::get('/reports', [\Dooz\Worklog\Http\Controllers\ReportWebController::class, 'index'])->name('reports.index');
    Route::get('/reports/weekly', [\Dooz\Worklog\Http\Controllers\ReportWebController::class, 'weekly'])->name('reports.weekly');
    Route::get('/reports/export', [\Dooz\Worklog\Http\Controllers\ReportWebController::class, 'exportCsv'])->name('reports.export');

    // Time Logs
    Route::resource('time-logs', \Dooz\Worklog\Http\Controllers\TimeLogWebController::class);

    // Tasks (Task Ledger)
    Route::resource('tasks', \Dooz\Worklog\Http\Controllers\TaskWebController::class);
    Route::patch('/tasks/{id}/status', [\Dooz\Worklog\Http\Controllers\TaskWebController::class, 'updateStatus'])->name('tasks.updateStatus');

    // Invoices
    Route::resource('invoices', \Dooz\Worklog\Http\Controllers\InvoiceWebController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/invoices/{id}/status', [\Dooz\Worklog\Http\Controllers\InvoiceWebController::class, 'updateStatus'])->name('invoices.updateStatus');
});
