<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o utilizador gere pelo menos uma empresa e injecta-a no pedido,
 * evitando que cada controller repita a mesma resolucao.
 */
class EnsureCompanyMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $company = $user?->primaryCompany();

        if ($company === null) {
            return redirect()->route('business.claim.create')
                ->with('info', 'Associa a tua conta a uma empresa para acederes a esta área.');
        }

        $request->attributes->set('company', $company);

        return $next($request);
    }
}
