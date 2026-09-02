<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\Companies\Enums\CompanyStatus;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyCategory;
use App\Domain\Companies\Models\CompanySlug;
use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Ratings\Enums\StatsPeriod;
use App\Domain\Ratings\Models\CompanyStat;
use App\Domain\Seo\Services\SchemaBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $term = (string) $request->query('q', '');
        $order = (string) $request->query('ordenar', 'reclamacoes');

        $companies = Company::public()
            ->with('category:id,name,slug')
            ->search($term)
            ->when($order === 'indice', fn (Builder $q) => $q->orderByDesc('satisfaction_index'))
            ->when($order === 'nome', fn (Builder $q) => $q->orderBy('name'))
            ->when($order === 'reclamacoes', fn (Builder $q) => $q->orderByDesc('published_complaints_count'))
            ->paginate((int) config('queixame.search.companies_per_page'))
            ->withQueryString();

        $this->seo()
            ->title('Diretório de empresas')
            ->description('Consulta o historial de reclamações, a taxa de resposta e o índice de satisfação de milhares de empresas antes de comprares.')
            ->canonical($term !== '' ? route('companies.index') : $request->url())
            ->pagination($companies->previousPageUrl(), $companies->nextPageUrl());

        if ($term !== '') {
            $this->seo()->noindex(follow: true);
        }

        $this->breadcrumbs([['label' => 'Empresas', 'url' => route('companies.index')]]);

        return view('public.companies.index', [
            'companies' => $companies,
            'categories' => CompanyCategory::whereNull('parent_id')
                ->withCount(['companies' => fn ($q) => $q->public()])
                ->orderBy('name')
                ->get(),
            'term' => $term,
            'order' => $order,
        ]);
    }

    public function category(Request $request, CompanyCategory $category): View
    {
        $companies = Company::public()
            ->where('category_id', $category->id)
            ->with('category:id,name,slug')
            ->orderByDesc('published_complaints_count')
            ->paginate((int) config('queixame.search.companies_per_page'))
            ->withQueryString();

        $this->seo()
            ->title($category->meta_title ?: 'Empresas de '.$category->name)
            ->description($category->meta_description ?: 'Reclamações, taxas de resposta e índices de satisfação das empresas do setor '.mb_strtolower($category->name).'.')
            ->canonical(route('companies.category', $category))
            ->pagination($companies->previousPageUrl(), $companies->nextPageUrl());

        $this->breadcrumbs([
            ['label' => 'Empresas', 'url' => route('companies.index')],
            ['label' => $category->name, 'url' => route('companies.category', $category)],
        ]);

        return view('public.companies.category', [
            'category' => $category,
            'companies' => $companies,
        ]);
    }

    public function show(Request $request, string $company): View|RedirectResponse
    {
        $model = $this->resolveCompany($company);

        if ($model instanceof RedirectResponse) {
            return $model;
        }

        $model->load('category:id,name,slug');

        $complaints = Complaint::published()
            ->where('company_id', $model->id)
            ->forCards()
            ->latest('published_at')
            ->limit(6)
            ->get();

        $history = CompanyStat::where('company_id', $model->id)
            ->where('period_type', StatsPeriod::Monthly->value)
            ->orderByDesc('period_start')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $breakdown = CompanyStat::where('company_id', $model->id)
            ->where('period_type', StatsPeriod::Rolling12->value)
            ->latest('period_start')
            ->first();

        $this->configureCompanySeo($model);

        return view('public.companies.show', [
            'company' => $model,
            'complaints' => $complaints,
            'history' => $history,
            'breakdown' => $breakdown,
            'stageCounts' => $this->stageCounts($model),
        ]);
    }

    public function complaints(Request $request, string $company): View|RedirectResponse
    {
        $model = $this->resolveCompany($company);

        if ($model instanceof RedirectResponse) {
            return $model;
        }

        $stage = $request->query('estado');

        $complaints = Complaint::published()
            ->where('company_id', $model->id)
            ->when($stage, fn (Builder $q) => $q->where('stage', $stage))
            ->forCards()
            ->latest('published_at')
            ->paginate((int) config('queixame.search.per_page'))
            ->withQueryString();

        $page = (int) $request->query('page', '1');

        $this->seo()
            ->title('Reclamações sobre '.$model->name.($page > 1 ? ' — página '.$page : ''))
            ->description('Todas as reclamações publicadas sobre '.$model->name.', com as respostas da empresa e o desfecho de cada caso.')
            ->canonical($stage ? route('companies.complaints', $model->slug) : $request->url())
            ->pagination($complaints->previousPageUrl(), $complaints->nextPageUrl());

        if ($stage) {
            $this->seo()->noindex(follow: true);
        }

        $this->breadcrumbs([
            ['label' => 'Empresas', 'url' => route('companies.index')],
            ['label' => $model->name, 'url' => $model->url()],
            ['label' => 'Reclamações', 'url' => null],
        ]);

        return view('public.companies.complaints', [
            'company' => $model,
            'complaints' => $complaints,
            'stages' => ComplaintStage::publicFilterable(),
            'activeStage' => $stage,
        ]);
    }

    /** Sugestões para o assistente de reclamação. */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['companies' => []]);
        }

        $companies = Company::public()
            ->search($term)
            ->orderByDesc('published_complaints_count')
            ->limit((int) config('queixame.search.autocomplete_limit'))
            ->get(['id', 'name', 'slug', 'district', 'category_id', 'published_complaints_count'])
            ->map(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'initials' => $company->initials(),
                'meta' => trim(collect([
                    $company->category?->name,
                    $company->published_complaints_count > 0
                        ? $company->published_complaints_count.' reclamações'
                        : 'Sem reclamações publicadas',
                ])->filter()->implode(' · ')),
            ]);

        return response()->json(['companies' => $companies]);
    }

    // -----------------------------------------------------------------

    /**
     * Resolve o slug pedido, honrando o histórico de slugs.
     *
     * Uma empresa que mudou de nome, ou que foi fundida com uma duplicada,
     * responde com 301 para o URL canónico atual em vez de 404.
     */
    private function resolveCompany(string $slug): Company|RedirectResponse
    {
        $company = Company::where('slug', $slug)->first();

        if ($company === null) {
            $historic = CompanySlug::where('slug', $slug)->with('company')->first();

            abort_if($historic?->company === null, 404);

            return redirect()->route('companies.show', $historic->company->slug, 301);
        }

        if ($company->status === CompanyStatus::Merged && $company->merged_into_id) {
            return redirect()->route('companies.show', $company->mergedInto->slug, 301);
        }

        abort_unless($company->isPublic(), 404);

        return $company;
    }

    private function configureCompanySeo(Company $company): void
    {
        $count = $company->published_complaints_count;

        $description = $count > 0
            ? sprintf(
                '%s: %d reclamações publicadas, %s de taxa de resposta e %s de taxa de resolução. Vê o que aconteceu a outros consumidores.',
                $company->name,
                $count,
                $company->response_rate !== null ? round($company->response_rate).'%' : 'sem dados',
                $company->resolution_rate !== null ? round($company->resolution_rate).'%' : 'sem dados',
            )
            : sprintf('Ficha de %s no queixa.me. Ainda não existem reclamações publicadas sobre esta empresa.', $company->name);

        $this->seo()
            ->title($company->meta_title ?: 'Reclamações e avaliações de '.$company->name)
            ->description($company->meta_description ?: $description)
            ->canonical($company->url())
            ->image($company->logoUrl())
            ->schema(SchemaBuilder::company($company));

        // Fichas sem conteúdo real ficam fora do índice até terem substância.
        if (! $company->shouldBeIndexed()) {
            $this->seo()->noindex(follow: true);
        }

        $this->breadcrumbs([
            ['label' => 'Empresas', 'url' => route('companies.index')],
            ...($company->category ? [['label' => $company->category->name, 'url' => route('companies.category', $company->category)]] : []),
            ['label' => $company->name, 'url' => $company->url()],
        ]);
    }

    /** @return array<string,int> */
    private function stageCounts(Company $company): array
    {
        return Complaint::published()
            ->where('company_id', $company->id)
            ->selectRaw('stage, count(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->all();
    }
}
