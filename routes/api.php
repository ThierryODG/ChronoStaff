<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClockController;
use App\Http\Controllers\Api\CompanySettingsController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeHistoryController;
use App\Http\Controllers\Api\EmployeeManagementController;
use App\Http\Controllers\Api\EmployeeMonthlyReportController;
use App\Http\Controllers\Api\JustificationController;
use App\Http\Controllers\Api\LegalController;
use App\Http\Controllers\Api\ManualAdjustmentController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - ChronoStaff
|--------------------------------------------------------------------------
*/

// Route heure serveur (publique — utilisée par l'app mobile pour la synchronisation)
Route::get('/time', function () {
    return response()->json([
        'server_time' => now()->toIso8601String(),
        'timezone' => config('app.timezone'),
    ]);
});

// ==========================================
// 1. Authentification & Accès Public
// ==========================================
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/kiosk-connect', [AuthController::class, 'kioskConnect']);
});

Route::get('/notifications/vapid-public-key', [PushSubscriptionController::class, 'publicKey']);

// Le pointage par scan QR ou code PIN peut être appelé directement
// (ex: scan sur borne partagée ou par l'app avant session complète)
Route::post('/clock/punch', [ClockController::class, 'punch']);

// ==========================================
// 2. Routes Authentifiées (Employés & Admin)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // Profil & Déconnexion
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::post('/notifications/subscriptions', [PushSubscriptionController::class, 'store']);
    Route::delete('/notifications/subscriptions', [PushSubscriptionController::class, 'destroy']);

    // Acceptation légale (CGU / Confidentialité)
    Route::prefix('legal')->group(function () {
        Route::get('/status', [LegalController::class, 'status']);
        Route::post('/accept', [LegalController::class, 'accept']);
    });

    // Pointage & Synchronisation Offline (Employé)
    Route::prefix('clock')->group(function () {
        Route::post('/sync', [SyncController::class, 'sync']); // Envoi groupé offline
    });

    // Historique des pointages de l'employé connecté
    Route::get('/employee/history', [EmployeeHistoryController::class, 'index']);
    Route::get('/employee/justifications', [JustificationController::class, 'index']);
    Route::post('/employee/justification', [JustificationController::class, 'store']);
    Route::get('/employee/monthly-report', [EmployeeMonthlyReportController::class, 'getReport']);

    // ==========================================
    // 3. Espace Administrateur
    // ==========================================
    Route::middleware('admin')->prefix('admin')->group(function () {

        // Dashboard temps réel "Qui est là ?"
        Route::get('/dashboard/who-is-here', [AdminDashboardController::class, 'whoIsHere']);

        // Rapports d'heures et export CSV
        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/export-csv', [ReportController::class, 'exportCsv']);

        // Gestion des employés (CRUD complet)
        Route::apiResource('employees', EmployeeManagementController::class);
        Route::post('/employees/{employee}/regenerate-qr', [EmployeeManagementController::class, 'regenerateQr']);

        // Gestion des départements
        Route::apiResource('departments', DepartmentController::class);

        // Ajustements manuels des pointages (avec note obligatoire)
        Route::post('/manual-adjustments', [ManualAdjustmentController::class, 'store']);
        Route::put('/manual-adjustments/{timeLog}', [ManualAdjustmentController::class, 'update']);

        // Paramètres de l'entreprise (adresse, géolocalisation, arrondis, pauses)
        Route::get('/company/settings', [CompanySettingsController::class, 'show']);
        Route::put('/company/settings', [CompanySettingsController::class, 'update']);
    });
});
