<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashFlowController;
use App\Http\Controllers\Api\ClientReportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\P2pOperationController;
use App\Http\Controllers\Api\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Api\Admin\CollaboratorController;
use App\Http\Controllers\Api\Admin\CommissionRuleController;
use App\Http\Controllers\Api\Admin\ClientTransactionController;
use App\Http\Controllers\Api\Admin\CommissionTransactionController;
use App\Http\Controllers\Api\Admin\InternalTransactionController;
use App\Http\Controllers\Api\Admin\InternalReportController;
use App\Http\Controllers\Api\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::get('/dashboard/client', [DashboardController::class, 'clientMetrics']);

    Route::get('/client/reports', [ClientReportController::class, 'index']);
    Route::get('/client/reports/{year}/{month}', [ClientReportController::class, 'show']);
    Route::get('/client/reports/{year}/{month}/pdf', [ClientReportController::class, 'pdf']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'adminMetrics']);

        Route::apiResource('clients', AdminClientController::class);
        Route::apiResource('collaborators', CollaboratorController::class);
        Route::get('collaborators/{collaborator}/report', [CollaboratorController::class, 'report']);
        Route::apiResource('commission-rules', CommissionRuleController::class);
        Route::apiResource('client-transactions', ClientTransactionController::class);
        Route::apiResource('commission-transactions', CommissionTransactionController::class);
        Route::apiResource('internal-transactions', InternalTransactionController::class);

        Route::apiResource('cash-flow', CashFlowController::class);
        Route::get('currencies', fn () => \App\Models\Currency::orderBy('code')->get());
        Route::get('networks', fn () => \App\Models\Network::where('is_active', true)->get());
        Route::apiResource('p2p-operations', P2pOperationController::class);

        Route::get('reports', [ReportController::class, 'index']);
        Route::post('reports/generate', [ReportController::class, 'generate']);
        Route::get('reports/{report}', [ReportController::class, 'show']);
        Route::get('reports/{report}/pdf', [ReportController::class, 'pdf']);
        Route::post('reports/batch-pdf', [ReportController::class, 'batchPdf']);
        Route::post('reports/{report}/publish', [ReportController::class, 'publish']);
        Route::delete('reports/{report}', [ReportController::class, 'destroy']);

        Route::get('internal-reports', [InternalReportController::class, 'index']);
        Route::post('internal-reports/generate', [InternalReportController::class, 'generate']);
        Route::post('internal-reports/batch-pdf', [InternalReportController::class, 'batchPdf']);
        Route::delete('internal-reports/{internalReport}', [InternalReportController::class, 'destroy']);
    });
});
