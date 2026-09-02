<?php

declare(strict_types=1);

use App\Http\Controllers\Consumer\ActivityController;
use App\Http\Controllers\Consumer\ComplaintWizardController;
use App\Http\Controllers\Consumer\MessageController;
use App\Http\Controllers\Consumer\MyComplaintController;
use App\Http\Controllers\Consumer\PrivacyController;
use App\Http\Controllers\Consumer\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Área do consumidor
|--------------------------------------------------------------------------
|
| Tudo aqui é privado: o middleware 'noindex' garante que nenhuma destas
| páginas entra nos motores de busca, mesmo que um URL seja partilhado.
*/

Route::middleware(['auth', 'noindex'])->group(function (): void {

    // --- Assistente de nova reclamação --------------------------------
    // Cada passo tem URL próprio e o rascunho é gravado no servidor, para
    // que o utilizador possa fechar o browser e retomar mais tarde.
    Route::prefix('reclamar')->name('complaints.')->group(function (): void {
        Route::get('/', [ComplaintWizardController::class, 'start'])
            ->withoutMiddleware('auth')
            ->name('create');
        Route::post('/empresa', [ComplaintWizardController::class, 'storeCompany'])->name('wizard.company');
        Route::get('/{complaint:uuid}/descricao', [ComplaintWizardController::class, 'description'])->name('wizard.description');
        Route::post('/{complaint:uuid}/descricao', [ComplaintWizardController::class, 'storeDescription'])->name('wizard.description.store');
        Route::get('/{complaint:uuid}/detalhes', [ComplaintWizardController::class, 'details'])->name('wizard.details');
        Route::post('/{complaint:uuid}/detalhes', [ComplaintWizardController::class, 'storeDetails'])->name('wizard.details.store');
        Route::get('/{complaint:uuid}/dados', [ComplaintWizardController::class, 'contact'])->name('wizard.contact');
        Route::post('/{complaint:uuid}/dados', [ComplaintWizardController::class, 'storeContact'])->name('wizard.contact.store');
        Route::get('/{complaint:uuid}/confirmar', [ComplaintWizardController::class, 'review'])->name('wizard.review');
        Route::post('/{complaint:uuid}/submeter', [ComplaintWizardController::class, 'submit'])
            ->middleware('throttle:10,60')
            ->name('wizard.submit');
        Route::delete('/{complaint:uuid}/anexo/{attachment:uuid}', [ComplaintWizardController::class, 'destroyAttachment'])->name('wizard.attachment.destroy');
        Route::delete('/{complaint:uuid}', [ComplaintWizardController::class, 'destroy'])->name('wizard.destroy');
    });

    Route::prefix('conta')->name('consumer.')->group(function (): void {
        Route::get('/', [ActivityController::class, 'dashboard'])->name('dashboard');
        Route::get('/atividade', [ActivityController::class, 'index'])->name('activity');

        // --- As minhas reclamações -----------------------------------
        Route::get('/reclamacoes', [MyComplaintController::class, 'index'])->name('complaints.index');
        Route::get('/reclamacoes/{complaint:uuid}', [MyComplaintController::class, 'show'])->name('complaints.show');
        Route::post('/reclamacoes/{complaint:uuid}/responder', [MyComplaintController::class, 'reply'])
            ->middleware('throttle:20,60')
            ->name('complaints.reply');
        Route::post('/reclamacoes/{complaint:uuid}/resolver', [MyComplaintController::class, 'confirmResolution'])->name('complaints.resolve');
        Route::post('/reclamacoes/{complaint:uuid}/nao-resolvida', [MyComplaintController::class, 'markUnresolved'])->name('complaints.unresolved');
        Route::post('/reclamacoes/{complaint:uuid}/reabrir', [MyComplaintController::class, 'reopen'])->name('complaints.reopen');

        // --- Mensagens privadas --------------------------------------
        Route::get('/mensagens', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/mensagens/{conversation:uuid}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('/mensagens/{conversation:uuid}', [MessageController::class, 'store'])
            ->middleware('throttle:30,10')
            ->name('messages.store');
        Route::post('/mensagens/{conversation:uuid}/estado', [MessageController::class, 'toggleRead'])->name('messages.toggle-read');
        Route::post('/mensagens/{conversation:uuid}/fechar', [MessageController::class, 'close'])->name('messages.close');

        // --- Perfil ---------------------------------------------------
        Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/perfil/fotografia', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::delete('/perfil/fotografia', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
        Route::patch('/perfil/palavra-passe', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // Alteração de email em duas fases.
        Route::post('/perfil/email', [ProfileController::class, 'requestEmailChange'])
            ->middleware('throttle:5,60')
            ->name('profile.email.request');
        Route::get('/perfil/email/confirmar/{token}', [ProfileController::class, 'confirmEmailChange'])->name('profile.email.confirm');
        Route::delete('/perfil/email', [ProfileController::class, 'cancelEmailChange'])->name('profile.email.cancel');

        // Telefone: fluxo completo, envio de SMS ainda por integrar.
        Route::post('/perfil/telefone', [ProfileController::class, 'requestPhoneVerification'])
            ->middleware('throttle:5,60')
            ->name('profile.phone.request');
        Route::post('/perfil/telefone/confirmar', [ProfileController::class, 'confirmPhone'])
            ->middleware('throttle:10,10')
            ->name('profile.phone.confirm');

        // --- Privacidade e RGPD ---------------------------------------
        Route::get('/privacidade', [PrivacyController::class, 'index'])->name('privacy');
        Route::patch('/privacidade/comunicacoes', [PrivacyController::class, 'updateMarketing'])->name('privacy.marketing');
        Route::post('/privacidade/exportar', [PrivacyController::class, 'requestExport'])
            ->middleware('throttle:3,1440')
            ->name('privacy.export');
        Route::post('/privacidade/eliminar', [PrivacyController::class, 'requestDeletion'])->name('privacy.delete');
    });
});
