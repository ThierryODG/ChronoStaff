<?php

use App\Http\Controllers\Web\AdminAuthController;
use App\Http\Controllers\Web\AdminCompanyController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminDepartmentController;
use App\Http\Controllers\Web\AdminEmployeeController;
use App\Http\Controllers\Web\AdminManualAdjustmentController;
use App\Http\Controllers\Web\AdminReportController;
use Illuminate\Support\Facades\Route;

// Page d'accueil : Site Vitrine officiel de ChronoStaff
Route::get('/', function () {
    return view('landing');
});

// Espace propriétaire de la plateforme : accès direct via /admin, sans lien public
Route::get('/admin', [AdminAuthController::class, 'showSuperAdminLoginForm'])->name('superadmin.login');
Route::post('/admin', [AdminAuthController::class, 'requestOwnerLoginCode'])->name('superadmin.request-code');
Route::post('/admin/verify-code', [AdminAuthController::class, 'verifyOwnerLoginCode'])->name('superadmin.verify-code');

// Accès à l'application mobile PWA pour les agents
Route::get('/app', function () {
    return redirect('/mobile/index.html');
});
Route::get('/mobile', function () {
    return redirect('/mobile/index.html');
});

// Authentification Administrateur Web
Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/login', [AdminAuthController::class, 'login']);
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
Route::post('/admin/logout', [AdminAuthController::class, 'logout']);

// Espace d'Administration Protégé
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // 1. Dashboard "Qui est là ?"
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // 2. Gestion des Employés
    Route::resource('employees', AdminEmployeeController::class);
    Route::post('/employees/{employee}/toggle-status', [AdminEmployeeController::class, 'toggleStatus'])->name('employees.toggle-status');
    Route::post('/employees/{employee}/regenerate-qr', [AdminEmployeeController::class, 'regenerateQr'])->name('employees.regenerate-qr');
    Route::get('/employees/{employee}/badge', [AdminEmployeeController::class, 'badge'])->name('employees.badge');

    // 3. Gestion des Départements
    Route::resource('departments', AdminDepartmentController::class)->except(['create', 'show', 'edit']);

    // 4. Rapports & Exportations
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-csv', [AdminReportController::class, 'exportCsv'])->name('reports.export-csv');
    Route::get('/reports/export-pdf', [AdminReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::get('/reports/export-word', [AdminReportController::class, 'exportWord'])->name('reports.export-word');

    // 5. Ajustements Manuels & Justifications
    Route::get('/adjustments', [AdminManualAdjustmentController::class, 'index'])->name('adjustments.index');
    Route::post('/adjustments', [AdminManualAdjustmentController::class, 'store'])->name('adjustments.store');
    Route::post('/adjustments/justifications/{justification}/status', [AdminManualAdjustmentController::class, 'updateJustificationStatus'])->name('adjustments.justifications.status');

    // 6. Localisation et Paramètres Entreprise
    Route::get('/company/settings', [AdminCompanyController::class, 'edit'])->name('company.edit');
    Route::put('/company/settings', [AdminCompanyController::class, 'update'])->name('company.update');
    Route::put('/company/password', [AdminCompanyController::class, 'updatePassword'])->name('company.password.update');
    Route::post('/company/managers', [AdminCompanyController::class, 'storeManager'])->name('company.managers.store');
    Route::delete('/company/managers/{manager}', [AdminCompanyController::class, 'destroyManager'])->name('company.managers.destroy');
    Route::post('/company/kiosks', [AdminCompanyController::class, 'storeKiosk'])->name('company.kiosks.store');
    Route::post('/company/kiosks/{kiosk}/regenerate-key', [AdminCompanyController::class, 'regenerateKioskKey'])->name('company.kiosks.regenerate-key');
    Route::delete('/company/kiosks/{kiosk}', [AdminCompanyController::class, 'destroyKiosk'])->name('company.kiosks.destroy');
});

// Espace Super-Admin (Propriétaire de la plateforme / Gestion Multi-Entreprises)
Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/companies', [\App\Http\Controllers\Web\SuperAdminCompanyController::class, 'index'])->name('companies.index');
    Route::post('/companies', [\App\Http\Controllers\Web\SuperAdminCompanyController::class, 'store'])->name('companies.store');
    Route::put('/companies/{company}', [\App\Http\Controllers\Web\SuperAdminCompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}', [\App\Http\Controllers\Web\SuperAdminCompanyController::class, 'destroy'])->name('companies.destroy');
    Route::post('/companies/{company}/impersonate', [\App\Http\Controllers\Web\SuperAdminCompanyController::class, 'impersonate'])->name('companies.impersonate');
    Route::get('/subscriptions', [\App\Http\Controllers\Web\SuperAdminCompanyController::class, 'subscriptions'])->name('subscriptions.index');
    Route::post('/system/clear-cache', [\App\Http\Controllers\Web\SuperAdminCompanyController::class, 'clearCache'])->name('system.clear-cache');
});

// Quitter le mode impersonation
Route::post('/superadmin/impersonate/leave', [\App\Http\Controllers\Web\SuperAdminCompanyController::class, 'leaveImpersonation'])->name('superadmin.impersonate.leave')->middleware('auth');

