<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterBusinessController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticação
|--------------------------------------------------------------------------
|
| Limites de tentativas em todas as rotas que aceitam credenciais ou enviam
| email, para travar enumeração de contas e abuso de envio.
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/entrar', [LoginController::class, 'create'])->name('login');
    Route::post('/entrar', [LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::get('/registar', [RegisterController::class, 'create'])->name('register');
    Route::post('/registar', [RegisterController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('register.store');

    Route::get('/registar/empresa', [RegisterBusinessController::class, 'create'])->name('register.business');
    Route::post('/registar/empresa', [RegisterBusinessController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('register.business.store');

    Route::get('/recuperar-palavra-passe', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/recuperar-palavra-passe', [PasswordResetController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('password.email');
    Route::get('/redefinir-palavra-passe/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/redefinir-palavra-passe', [PasswordResetController::class, 'update'])
        ->middleware('throttle:5,10')
        ->name('password.update');

    // Login social. As rotas existem sempre; o controller devolve 404 quando
    // o fornecedor não está configurado.
    Route::get('/auth/{provider}/redirecionar', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', ['google', 'apple'])
        ->name('social.redirect');
    Route::match(['get', 'post'], '/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['google', 'apple'])
        ->name('social.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/verificar-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verificar-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/verificar-email/reenviar', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('/sair', [LoginController::class, 'destroy'])->name('logout');
});
