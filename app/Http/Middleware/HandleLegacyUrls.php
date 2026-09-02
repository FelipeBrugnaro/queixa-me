<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Seo\Models\NotFoundLog;
use App\Domain\Seo\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * URLs permanentes.
 *
 * PROBLEMA: num portal com centenas de milhares de paginas indexadas, cada
 * 404 e trafego e autoridade perdidos. Fusoes de empresas duplicadas,
 * correcoes de slug e reorganizacoes acontecem sempre.
 *
 * SOLUCAO: uma tabela de redirecionamentos consultada antes de devolver 404,
 * com cache para nao pesar no caminho critico, e registo dos 404 restantes
 * para que a equipa possa recuperar ligacoes valiosas.
 */
class HandleLegacyUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404 || ! $request->isMethod('GET')) {
            return $response;
        }

        $path = '/'.ltrim($request->path(), '/');

        $redirect = Cache::remember(
            'redirect:'.md5($path),
            now()->addHours(6),
            fn () => Redirect::where('from_path', $path)->first(),
        );

        if ($redirect) {
            $redirect->increment('hits');
            $redirect->forceFill(['last_hit_at' => now()])->saveQuietly();

            return redirect($redirect->to_path, $redirect->status_code);
        }

        $this->logNotFound($path, $request->headers->get('referer'));

        return $response;
    }

    private function logNotFound(string $path, ?string $referer): void
    {
        // Silencioso por desenho: registar 404 nunca deve fazer falhar o pedido.
        rescue(function () use ($path, $referer): void {
            $log = NotFoundLog::firstOrNew(['path' => $path]);
            $log->hits = ($log->hits ?? 0) + 1;
            $log->last_referer = $referer ? substr($referer, 0, 255) : $log->last_referer;
            $log->last_hit_at = now();
            $log->save();
        }, report: false);
    }
}
