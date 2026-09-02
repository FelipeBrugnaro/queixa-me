<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyCategory;
use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Seo\Services\SchemaBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $complaints = Complaint::published()
            ->forCards()
            ->when($filters['q'], fn (Builder $q, string $term) => $q->search($term))
            ->when($filters['empresa'], fn (Builder $q, string $slug) => $q->whereHas(
                'company', fn (Builder $c) => $c->where('slug', $slug)
            ))
            ->when($filters['categoria'], fn (Builder $q, string $slug) => $q->whereHas(
                'category', fn (Builder $c) => $c->where('slug', $slug)
            ))
            ->when($filters['estado'], fn (Builder $q, string $stage) => $q->where('stage', $stage))
            ->when($filters['distrito'], fn (Builder $q, string $district) => $q->where('district', $district))
            ->when($filters['periodo'], fn (Builder $q, string $period) => $q->where(
                'published_at', '>=', now()->subDays((int) $period)
            ))
            ->when($filters['ordenar'] === 'antigas', fn (Builder $q) => $q->oldest('published_at'))
            ->when($filters['ordenar'] === 'populares', fn (Builder $q) => $q->orderByDesc('views_count'))
            ->when($filters['ordenar'] === 'respondidas', fn (Builder $q) => $q->whereNotNull('first_response_at')->latest('first_response_at'))
            ->when($filters['ordenar'] === 'recentes', fn (Builder $q) => $q->latest('published_at'))
            ->paginate((int) config('queixame.search.per_page'))
            ->withQueryString();

        $this->configureSeo($request, $filters, $complaints);

        return view('public.complaints.index', [
            'complaints' => $complaints,
            'filters' => $filters,
            'categories' => CompanyCategory::orderBy('name')->get(['id', 'name', 'slug']),
            'stages' => ComplaintStage::publicFilterable(),
            'districts' => $this->districts(),
            'activeCompany' => $filters['empresa']
                ? Company::where('slug', $filters['empresa'])->first(['id', 'name', 'slug'])
                : null,
        ]);
    }

    public function show(Request $request, Complaint $complaint): View
    {
        abort_unless($complaint->isPublished(), 404);

        $complaint->load([
            'company:id,name,slug,logo_path,status,satisfaction_index,response_rate,resolution_rate,published_complaints_count,category_id',
            'company.category:id,name,slug',
            'category:id,name,slug',
            'publicReplies' => fn ($q) => $q->with('company:id,name,slug,logo_path'),
            'publicEvents',
            'publicAttachments',
        ]);

        // Contador de visitas sem bloquear a resposta nem invalidar cache HTTP.
        $complaint->incrementQuietly('views_count');

        $related = Complaint::published()
            ->where('id', '!=', $complaint->id)
            ->when($complaint->company_id, fn (Builder $q) => $q->where('company_id', $complaint->company_id))
            ->forCards()
            ->latest('published_at')
            ->limit(4)
            ->get();

        $this->seo()
            ->title($complaint->title)
            ->description($complaint->excerpt(155))
            ->canonical($complaint->url())
            ->article(
                $complaint->published_at?->toIso8601String(),
                $complaint->updated_at?->toIso8601String(),
            )
            ->schema(SchemaBuilder::complaint($complaint));

        if (! $complaint->shouldBeIndexed()) {
            $this->seo()->noindex();
        }

        $this->breadcrumbs([
            ['label' => 'Reclamações', 'url' => route('complaints.index')],
            ...($complaint->company ? [['label' => $complaint->company->name, 'url' => $complaint->company->url()]] : []),
            ['label' => $complaint->title, 'url' => null],
        ]);

        return view('public.complaints.show', [
            'complaint' => $complaint,
            'related' => $related,
        ]);
    }

    /** @return array<string,?string> */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'empresa' => ['nullable', 'string', 'max:160'],
            'categoria' => ['nullable', 'string', 'max:160'],
            'estado' => ['nullable', 'string', 'in:'.implode(',', array_column(ComplaintStage::publicFilterable(), 'value'))],
            'distrito' => ['nullable', 'string', 'max:60'],
            'periodo' => ['nullable', 'in:7,30,90,365'],
            'ordenar' => ['nullable', 'in:recentes,antigas,populares,respondidas'],
        ]);

        return [
            'q' => $validated['q'] ?? null,
            'empresa' => $validated['empresa'] ?? null,
            'categoria' => $validated['categoria'] ?? null,
            'estado' => $validated['estado'] ?? null,
            'distrito' => $validated['distrito'] ?? null,
            'periodo' => $validated['periodo'] ?? null,
            'ordenar' => $validated['ordenar'] ?? 'recentes',
        ];
    }

    /**
     * SEO de uma listagem filtrável.
     *
     * Regra: apenas a listagem sem filtros (e as suas páginas seguintes) é
     * indexável. Combinações de filtros geram um número virtualmente infinito
     * de URLs com o mesmo conteúdo — indexá-las diluiria o domínio e gastaria
     * orçamento de rastreio. O canonical aponta sempre para a versão limpa,
     * e as páginas 2+ mantêm canonical próprio para não perderem o conteúdo.
     */
    private function configureSeo(Request $request, array $filters, $complaints): void
    {
        $hasFilters = collect($filters)->except('ordenar')->filter()->isNotEmpty()
            || $filters['ordenar'] !== 'recentes';

        $page = (int) $request->query('page', '1');

        $title = 'Reclamações de consumidores';
        $description = 'Reclamações publicadas sobre empresas, encomendas, entregas, atendimento e serviços. Consulta o que aconteceu a outros consumidores e como a empresa respondeu.';

        if ($filters['empresa'] && $company = Company::where('slug', $filters['empresa'])->first()) {
            $title = 'Reclamações sobre '.$company->name;
        }

        if ($page > 1) {
            $title .= ' — página '.$page;
        }

        $this->seo()
            ->title($title)
            ->description($description)
            ->canonical($hasFilters ? route('complaints.index') : $request->url().($page > 1 ? '?page='.$page : ''))
            ->pagination(
                $complaints->previousPageUrl(),
                $complaints->nextPageUrl(),
            );

        if ($hasFilters) {
            // follow = true: as ligações filtradas continuam a ser seguidas,
            // é apenas a página em si que não deve ser indexada.
            $this->seo()->noindex(follow: true);
        }

        $this->breadcrumbs([['label' => 'Reclamações', 'url' => route('complaints.index')]]);
    }

    /** @return array<int,string> */
    private function districts(): array
    {
        return [
            'Aveiro', 'Beja', 'Braga', 'Bragança', 'Castelo Branco', 'Coimbra', 'Évora',
            'Faro', 'Guarda', 'Leiria', 'Lisboa', 'Portalegre', 'Porto', 'Santarém',
            'Setúbal', 'Viana do Castelo', 'Vila Real', 'Viseu',
            'Região Autónoma dos Açores', 'Região Autónoma da Madeira',
        ];
    }
}
