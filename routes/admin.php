<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminCompanyController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ModerationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administração e moderação
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'staff', 'noindex'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    // --- Fila de moderação --------------------------------------------
    Route::get('/moderacao', [ModerationController::class, 'index'])->name('moderation.index');
    Route::get('/moderacao/{complaint:uuid}', [ModerationController::class, 'show'])->name('moderation.show');
    Route::post('/moderacao/{complaint:uuid}/analisar', [ModerationController::class, 'startReview'])->name('moderation.start');
    Route::post('/moderacao/{complaint:uuid}/aprovar', [ModerationController::class, 'approve'])->name('moderation.approve');
    Route::post('/moderacao/{complaint:uuid}/alteracoes', [ModerationController::class, 'requestChanges'])->name('moderation.changes');
    Route::post('/moderacao/{complaint:uuid}/rejeitar', [ModerationController::class, 'reject'])->name('moderation.reject');
    Route::post('/moderacao/{complaint:uuid}/remover', [ModerationController::class, 'remove'])->name('moderation.remove');

    // --- Empresas ------------------------------------------------------
    Route::get('/empresas', [AdminCompanyController::class, 'index'])->name('companies.index');
    Route::get('/empresas/{company}', [AdminCompanyController::class, 'edit'])->name('companies.edit');
    Route::patch('/empresas/{company}', [AdminCompanyController::class, 'update'])->name('companies.update');
    Route::post('/empresas/{company}/aprovar', [AdminCompanyController::class, 'approve'])->name('companies.approve');
    Route::post('/empresas/{company}/fundir', [AdminCompanyController::class, 'merge'])->name('companies.merge');
    Route::post('/empresas/{company}/suspender', [AdminCompanyController::class, 'suspend'])->name('companies.suspend');
    Route::post('/empresas/reivindicacoes/{claim}/decidir', [AdminCompanyController::class, 'decideClaim'])->name('companies.claim.decide');

    // --- Utilizadores ---------------------------------------------------
    Route::get('/utilizadores', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/utilizadores/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/utilizadores/{user}/bloquear', [AdminUserController::class, 'block'])->name('users.block');
    Route::post('/utilizadores/{user}/desbloquear', [AdminUserController::class, 'unblock'])->name('users.unblock');
    Route::post('/utilizadores/{user}/anonimizar', [AdminUserController::class, 'anonymise'])
        ->middleware('password.confirm')
        ->name('users.anonymise');

    // --- Denúncias -------------------------------------------------------
    Route::get('/denuncias', [AdminReportController::class, 'index'])->name('reports.index');
    Route::post('/denuncias/{report}/decidir', [AdminReportController::class, 'decide'])->name('reports.decide');
});
