<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marca a resposta como nao indexavel.
 *
 * O cabecalho X-Robots-Tag e mais forte do que a meta tag: cobre tambem
 * respostas nao-HTML (JSON, ficheiros) e e respeitado mesmo quando a pagina
 * nao chega a ser renderizada.
 */
class PreventIndexing
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow', true);

        app(\App\Domain\Seo\Services\SeoManager::class)->noindex(follow: false);

        return $response;
    }
}
