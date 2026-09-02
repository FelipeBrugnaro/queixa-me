<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyCategory;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Content\Models\Post;
use App\Domain\Content\Models\PostCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Sitemaps particionados.
 *
 * Um sitemap único deixa de funcionar cedo: o limite é 50 000 URLs / 50 MB.
 * Um portal de reclamações ultrapassa isso no primeiro ano. Aqui geramos um
 * índice que aponta para blocos por tipo de conteúdo, o que também permite
 * ver no Search Console qual o tipo de página com problemas de indexação.
 */
class SitemapController extends Controller
{
    private const TYPES = ['paginas', 'empresas', 'reclamacoes', 'artigos', 'categorias'];

    public function index(): Response
    {
        $sitemaps = Cache::remember('sitemap:index', now()->addHours(3), function (): array {
            $chunk = (int) config('queixame.seo.sitemap_chunk_size');
            $entries = [];

            $counts = [
                'paginas' => 1,
                'empresas' => (int) ceil($this->companyQuery()->count() / $chunk),
                'reclamacoes' => (int) ceil($this->complaintQuery()->count() / $chunk),
                'artigos' => (int) ceil(Post::published()->count() / $chunk),
                'categorias' => 1,
            ];

            foreach ($counts as $type => $pages) {
                for ($page = 1; $page <= max(1, $pages); $page++) {
                    $entries[] = [
                        'loc' => route('sitemap.chunk', ['type' => $type, 'page' => $page]),
                        'lastmod' => now()->toAtomString(),
                    ];
                }
            }

            return $entries;
        });

        return $this->xml(view('public.sitemap.index', ['sitemaps' => $sitemaps]));
    }

    public function chunk(string $type, int $page): Response
    {
        abort_unless(in_array($type, self::TYPES, true), 404);
        abort_if($page < 1, 404);

        $urls = Cache::remember(
            "sitemap:{$type}:{$page}",
            now()->addHours(3),
            fn () => $this->urlsFor($type, $page),
        );

        abort_if($urls === [], 404);

        return $this->xml(view('public.sitemap.urlset', ['urls' => $urls]));
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Áreas privadas e páginas sem valor de indexação',
            'Disallow: /conta/',
            'Disallow: /gestao/',
            'Disallow: /admin/',
            'Disallow: /reclamar/',
            'Disallow: /anexo/',
            'Disallow: /pesquisar',
            'Disallow: /entrar',
            'Disallow: /registar',
            'Disallow: /recuperar-palavra-passe',
            'Disallow: /*?ordenar=',
            'Disallow: /*?estado=',
            'Disallow: /*?distrito=',
            '',
            'Sitemap: '.route('sitemap.index'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    // -----------------------------------------------------------------

    /** @return array<int,array{loc:string,lastmod:?string,changefreq:string,priority:string}> */
    private function urlsFor(string $type, int $page): array
    {
        $chunk = (int) config('queixame.seo.sitemap_chunk_size');
        $offset = ($page - 1) * $chunk;

        return match ($type) {
            'paginas' => $page === 1 ? $this->staticPages() : [],

            'empresas' => $this->companyQuery()
                ->orderBy('id')
                ->offset($offset)->limit($chunk)
                ->get(['slug', 'updated_at'])
                ->map(fn (Company $c) => [
                    'loc' => route('companies.show', $c->slug),
                    'lastmod' => $c->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ])->all(),

            'reclamacoes' => $this->complaintQuery()
                ->orderBy('id')
                ->offset($offset)->limit($chunk)
                ->get(['slug', 'updated_at'])
                ->map(fn (Complaint $c) => [
                    'loc' => route('complaints.show', $c->slug),
                    'lastmod' => $c->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ])->all(),

            'artigos' => Post::published()
                ->orderBy('id')
                ->offset($offset)->limit($chunk)
                ->get(['slug', 'updated_at'])
                ->map(fn (Post $p) => [
                    'loc' => route('blog.show', $p->slug),
                    'lastmod' => $p->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ])->all(),

            'categorias' => $page === 1 ? $this->categoryPages() : [],

            default => [],
        };
    }

    private function companyQuery()
    {
        return Company::public()->where('is_indexable', true);
    }

    private function complaintQuery()
    {
        return Complaint::published()->where('is_indexable', true)->whereNotNull('slug');
    }

    /** @return array<int,array<string,string>> */
    private function staticPages(): array
    {
        $pages = [
            ['route' => 'home', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['route' => 'complaints.index', 'priority' => '0.9', 'changefreq' => 'hourly'],
            ['route' => 'companies.index', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['route' => 'ranking', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['route' => 'compare', 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['route' => 'awards', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['route' => 'blog.index', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['route' => 'how-it-works', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'methodology', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'faq', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'about', 'priority' => '0.5', 'changefreq' => 'yearly'],
            ['route' => 'contact', 'priority' => '0.4', 'changefreq' => 'yearly'],
            ['route' => 'legal.terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'legal.privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'legal.data-protection', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'legal.moderation', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        return array_map(fn (array $page) => [
            'loc' => route($page['route']),
            'lastmod' => now()->startOfWeek()->toAtomString(),
            'changefreq' => $page['changefreq'],
            'priority' => $page['priority'],
        ], $pages);
    }

    /** @return array<int,array<string,string>> */
    private function categoryPages(): array
    {
        $companyCategories = CompanyCategory::orderBy('id')->get(['slug', 'updated_at'])
            ->map(fn (CompanyCategory $c) => [
                'loc' => route('companies.category', $c->slug),
                'lastmod' => $c->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ]);

        $postCategories = PostCategory::orderBy('id')->get(['slug', 'updated_at'])
            ->map(fn (PostCategory $c) => [
                'loc' => route('blog.category', $c->slug),
                'lastmod' => $c->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ]);

        return $companyCategories->concat($postCategories)->all();
    }

    private function xml($view): Response
    {
        return response($view->render(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
