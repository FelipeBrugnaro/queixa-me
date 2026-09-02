<?php

use App\Http\Middleware\EnsureCompanyMember;
use App\Http\Middleware\EnsureUserIsStaff;
use App\Http\Middleware\HandleLegacyUrls;
use App\Http\Middleware\PreventIndexing;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            HandleLegacyUrls::class,
        ]);

        $middleware->alias([
            'noindex' => PreventIndexing::class,
            'staff' => EnsureUserIsStaff::class,
            'company.member' => EnsureCompanyMember::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->isBusiness()
            ? route('business.dashboard')
            : route('consumer.dashboard'));

        // Confiar em proxies quando a aplicação corre atrás de balanceador.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
