<?php

use Illuminate\Support\Facades\Route;
use Dooz\Worklog\Http\Controllers\WorklogController;
use Dooz\Worklog\Http\Controllers\TaskController;
use Dooz\Worklog\Http\Controllers\ProjectController;
use Dooz\Worklog\Http\Controllers\TimeLogController;
use Dooz\Worklog\Http\Controllers\TaskApiController;
use Dooz\Worklog\Http\Controllers\ClientController;
use Dooz\Worklog\Http\Controllers\ReportController;
use Dooz\Worklog\Http\Controllers\InvoiceController;

Route::prefix('api/worklog')->middleware(['api'])->group(function () {
    // Health Check
    Route::get('/health', function () {
        return response()->json(['status' => 'ok', 'service' => 'Worklog']);
    })->withoutMiddleware(['auth:sanctum']);

    // Test routes (for debugging - remove in production)
    Route::get('/test-clients', [ClientController::class, 'index'])->withoutMiddleware(['auth:sanctum']);
    Route::post('/test-clients', [ClientController::class, 'store'])->withoutMiddleware(['auth:sanctum']);
    Route::get('/test-projects', [ProjectController::class, 'index'])->withoutMiddleware(['auth:sanctum']);
    Route::get('/test-tasks', [TaskController::class, 'index'])->withoutMiddleware(['auth:sanctum']);

    // Dashboard
    Route::get('/dashboard', [WorklogController::class, 'dashboard']);

    // Users (for assignment)
    Route::get('/users', function () {
        return \App\Models\User::select('id', 'name')->get();
    });

    // ==================
    // CLIENTS
    // ==================
    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/{id}', [ClientController::class, 'show']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::put('/clients/{id}', [ClientController::class, 'update']);
    Route::delete('/clients/{id}', [ClientController::class, 'destroy']);
    Route::get('/clients/{id}/projects', [ProjectController::class, 'byClient']);
    Route::get('/clients/{id}/unbilled-logs', [TimeLogController::class, 'unbilledByClient']);

    // ==================
    // PROJECTS
    // ==================
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

    // ==================
    // TASKS
    // ==================
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks-list', [TaskController::class, 'index']); // Alias for compatibility
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
    Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);

    // ==================
    // TIME LOGS
    // ==================
    Route::get('/time-logs', [TimeLogController::class, 'index']);
    Route::get('/time-logs/{id}', [TimeLogController::class, 'show']);
    Route::post('/time-logs', [TimeLogController::class, 'store']);
    Route::put('/time-logs/{id}', [TimeLogController::class, 'update']);
    Route::delete('/time-logs/{id}', [TimeLogController::class, 'destroy']);

    // ==================
    // REPORTS
    // ==================
    Route::get('/reports/by-client', [ReportController::class, 'byClient']);
    Route::get('/reports/by-project', [ReportController::class, 'byProject']);
    Route::get('/reports/billable-summary', [ReportController::class, 'billableSummary']);
    Route::get('/reports/weekly', [ReportController::class, 'weekly']);
    Route::get('/reports/export', [ReportController::class, 'exportCsv']);

    // ==================
    // INVOICES
    // ==================
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::patch('/invoices/{id}/status', [InvoiceController::class, 'updateStatus']);

    // ==================
    // QUICKY INTEGRATION
    // ==================
    Route::post('/tasks/from-quicky', [TaskApiController::class, 'createFromQuicky']);
    Route::get('/quicky/{quickyId}/linked-status', [TaskApiController::class, 'checkLinkedStatus']);
    Route::post('/quicky/{quickyId}/flag-tasks', [TaskApiController::class, 'flagLinkedTasks']);

    // ==================
    // AI FEATURES (Observer Mode)
    // ==================
    Route::get('/ai/effort-estimate', function (\Illuminate\Http\Request $request) {
        $taskName = $request->input('task_name', '');
        $projectId = $request->input('project_id');
        return response()->json(\Dooz\Worklog\Services\EffortAnalyzer::getEstimateForTask($taskName, $projectId));
    });
    Route::get('/ai/anomalies', function () {
        return response()->json(\Dooz\Worklog\Services\AnomalyDetector::getAllAnomalies());
    });
    Route::get('/ai/suggest-breakdown', function (\Illuminate\Http\Request $request) {
        $quickyId = $request->input('quicky_id');
        return response()->json(\Dooz\Worklog\Services\TaskBreakdownSuggester::getSuggestionsForQuicky($quickyId));
    });
});

