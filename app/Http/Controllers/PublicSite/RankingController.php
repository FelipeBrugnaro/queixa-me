<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyCategory;
use App\Domain\Seo\Services\SchemaBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    /**
     * Ordenações disponíveis.
     *
     * Nenhuma delas é "número de reclamações, decrescente": esse número
     * depende sobretudo da dimensão da empresa e transmitiria uma conclusão
     * errada. O volume aparece como coluna informativa, nunca como critério
     * de mérito.
     */
    private const SORTS = [
        'indice' => ['column' => 'satisfaction_index', 'label' => 'Índice de satisfação', 'dir' => 'desc'],
        'resposta' => ['column' => 'response_rate', 'label' => 'Taxa de resposta', 'dir' => 'desc'],
        'resolucao' => ['column' => 'resolution_rate', 'label' => 'Taxa de resolução', 'dir' => 'desc'],
        'avaliacao' => ['column' => 'average_rating', 'label' => 'Avaliação dos consumidores', 'dir' => 'desc'],
        'rapidez' => ['column' => 'avg_first_response_minutes', 'label' => 'Tempo médio de resposta', 'dir' => 'asc'],
    ];

    public function __invoke(Request $request): View
    {
        $sortKey = (string) $request->query('ordenar', 'indice');
        $sort = self::SORTS[$sortKey] ?? self::SORTS['indice'];
        $categorySlug = $request->query('categoria');
        $district = $request->query('distrito');

        $category = $categorySlug
            ? CompanyCategory::where('slug', $categorySlug)->first()
            : null;

        $companies = Company::rankable()
            ->with('category:id,name,slug')
            ->when($category, fn (Builder $q) => $q->where('category_id', $category->id))
            ->when($district, fn (Builder $q, string $value) => $q->where('district', $value))
            ->whereNotNull($sort['column'])
            ->orderBy($sort['column'], $sort['dir'])
            ->orderByDesc('published_complaints_count')
            ->paginate(25)
            ->withQueryString();

        $hasFilters = $categorySlug || $district || $sortKey !== 'indice';
        $page = (int) $request->query('page', '1');

        $this->seo()
            ->title($category
                ? 'Ranking de empresas — '.$category->name
                : 'Ranking de empresas'.($page > 1 ? ' — página '.$page : ''))
            ->description('Ranking das empresas que melhor respondem e resolvem reclamações. Índice de satisfação calculado sobre os últimos 12 meses, com correção estatística para não favorecer empresas com poucos casos.')
            ->canonical($hasFilters && ! $category ? route('ranking') : $request->url())
            ->pagination($companies->previousPageUrl(), $companies->nextPageUrl());

        if ($hasFilters && ! $category) {
            $this->seo()->noindex(follow: true);
        }

        if ($companies->isNotEmpty() && $page === 1) {
            $this->seo()->schema(SchemaBuilder::itemList(
                'Ranking de empresas no queixa.me',
                $companies->take(10)->values()->map(fn (Company $company, int $i) => [
                    'position' => $i + 1,
                    'name' => $company->name,
                    'url' => $company->url(),
                ])->all(),
            ));
        }

        $this->breadcrumbs([
            ['label' => 'Ranking', 'url' => route('ranking')],
            ...($category ? [['label' => $category->name, 'url' => null]] : []),
        ]);

        return view('public.ranking', [
            'companies' => $companies,
            'sorts' => self::SORTS,
            'sortKey' => array_key_exists($sortKey, self::SORTS) ? $sortKey : 'indice',
            'categories' => CompanyCategory::whereNull('parent_id')->orderBy('name')->get(['id', 'name', 'slug']),
            'activeCategory' => $category,
            'activeDistrict' => $district,
            'minimum' => (int) config('queixame.index.ranking_minimum_complaints'),
        ]);
    }
}
