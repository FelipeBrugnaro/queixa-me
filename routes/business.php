<?php

declare(strict_types=1);

use App\Http\Controllers\Business\BusinessComplaintController;
use App\Http\Controllers\Business\BusinessDashboardController;
use App\Http\Controllers\Business\BusinessMessageController;
use App\Http\Controllers\Business\BusinessProfileController;
use App\Http\Controllers\Business\BusinessTeamController;
use App\Http\Controllers\Business\CompanyClaimController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Área das empresas
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'noindex'])->prefix('gestao')->name('business.')->group(function (): void {

    // Reivindicação da ficha: acessível a quem ainda não tem empresa ligada.
    Route::get('/reivindicar', [CompanyClaimController::class, 'create'])->name('claim.create');
    Route::post('/reivindicar', [CompanyClaimController::class, 'store'])
        ->middleware('throttle:5,60')
        ->name('claim.store');
    Route::get('/reivindicar/pendente', [CompanyClaimController::class, 'pending'])->name('claim.pending');

    // O resto exige uma empresa ativa associada ao utilizador.
    Route::middleware('company.member')->group(function (): void {
        Route::get('/', BusinessDashboardController::class)->name('dashboard');

        Route::get('/reclamacoes', [BusinessComplaintController::class, 'index'])->name('complaints.index');
        Route::get('/reclamacoes/{complaint:uuid}', [BusinessComplaintController::class, 'show'])->name('complaints.show');
        Route::post('/reclamacoes/{complaint:uuid}/responder', [BusinessComplaintController::class, 'reply'])
            ->middleware('throttle:60,60')
            ->name('complaints.reply');
        Route::post('/reclamacoes/{complaint:uuid}/propor-solucao', [BusinessComplaintController::class, 'proposeResolution'])->name('complaints.propose');
        Route::post('/reclamacoes/{complaint:uuid}/mensagem', [BusinessComplaintController::class, 'startConversation'])->name('complaints.message');
        Route::post('/reclamacoes/{complaint:uuid}/denunciar', [BusinessComplaintController::class, 'report'])
            ->middleware('throttle:10,60')
            ->name('complaints.report');

        Route::get('/mensagens', [BusinessMessageController::class, 'index'])->name('messages.index');
        Route::get('/mensagens/{conversation:uuid}', [BusinessMessageController::class, 'show'])->name('messages.show');
        Route::post('/mensagens/{conversation:uuid}', [BusinessMessageController::class, 'store'])
            ->middleware('throttle:60,10')
            ->name('messages.store');

        Route::get('/estatisticas', [BusinessDashboardController::class, 'stats'])->name('stats');

        Route::get('/perfil', [BusinessProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/perfil', [BusinessProfileController::class, 'update'])->name('profile.update');
        Route::post('/perfil/logotipo', [BusinessProfileController::class, 'updateLogo'])->name('profile.logo');

        Route::get('/equipa', [BusinessTeamController::class, 'index'])->name('team.index');
        Route::post('/equipa', [BusinessTeamController::class, 'store'])->name('team.store');
        Route::patch('/equipa/{member}', [BusinessTeamController::class, 'update'])->name('team.update');
        Route::delete('/equipa/{member}', [BusinessTeamController::class, 'destroy'])->name('team.destroy');
    });
});
