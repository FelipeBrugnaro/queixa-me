<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyCategory;
use App\Domain\Ratings\Enums\StatsPeriod;
use App\Domain\Ratings\Models\CompanyStat;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    private const MAX_COMPANIES = 4;

    public function index(Request $request): View
    {
        $this->seo()
            ->title('Comparar marcas')
            ->description('Escolhe duas ou mais empresas e compara lado a lado a taxa de resposta, a taxa de resolução, o tempo médio de resposta e a avaliação dos consumidores.')
            ->canonical(route('compare'));

        $this->breadcrumbs([['label' => 'Comparar marcas', 'url' => route('compare')]]);

        return view('public.compare.index', [
            'categories' => CompanyCategory::whereNull('parent_id')->orderBy('name')->get(['id', 'name', 'slug']),
            'popular' => Company::rankable()
                ->orderByDesc('published_complaints_count')
                ->limit(40)
                ->get(['id', 'name', 'slug', 'logo_path', 'category_id', 'published_complaints_count', 'satisfaction_index', 'status']),
            'max' => self::MAX_COMPANIES,
        ]);
    }

    public function show(Request $request): View|RedirectResponse
    {
        $slugs = collect(explode(',', (string) $request->query('empresas', '')))
            ->map(fn (string $slug) => trim($slug))
            ->filter()
            ->unique()
            ->take(self::MAX_COMPANIES);

        if ($slugs->count() < 2) {
            return redirect()->route('compare')
                ->with('warning', 'Seleciona pelo menos duas empresas para comparar.');
        }

        $companies = Company::public()
            ->whereIn('slug', $slugs)
            ->with('category:id,name,slug')
            ->get()
            // Preservar a ordem pedida no URL torna o resultado partilhável
            // e estável: o mesmo link produz sempre a mesma tabela.
            ->sortBy(fn (Company $company) => $slugs->search($company->slug))
            ->values();

        if ($companies->count() < 2) {
            return redirect()->route('compare')
                ->with('warning', 'Não encontrámos empresas suficientes para essa comparação.');
        }

        $stats = CompanyStat::whereIn('company_id', $companies->pluck('id'))
            ->where('period_type', StatsPeriod::Rolling12->value)
            ->orderByDesc('period_start')
            ->get()
            ->unique('company_id')
            ->keyBy('company_id');

        $names = $companies->pluck('name')->implode(' vs ');

        $this->seo()
            ->title('Comparar '.$names)
            ->description('Comparação lado a lado dos indicadores de reclamação de '.$names.': taxa de resposta, taxa de resolução, tempo médio de resposta e índice de satisfação.')
            ->canonical(route('compare.show', ['empresas' => $companies->pluck('slug')->implode(',')]));

        $this->breadcrumbs([
            ['label' => 'Comparar marcas', 'url' => route('compare')],
            ['label' => $names, 'url' => null],
        ]);

        return view('public.compare.show', [
            'companies' => $companies,
            'stats' => $stats,
            'rows' => $this->rows(),
        ]);
    }

    /**
     * Definição das linhas da tabela comparativa.
     *
     * Cada linha declara como formatar e se "maior é melhor", para que o
     * destaque do vencedor seja calculado numa única passagem em vez de
     * espalhar condições pela view.
     */
    private function rows(): array
    {
        return [
            [
                'label' => 'Índice de satisfação',
                'hint' => 'Combina resposta, resolução, avaliação e rapidez',
                'value' => fn (Company $c) => $c->satisfaction_index,
                'format' => fn (?float $v) => $v !== null ? number_format($v, 0, ',', '').' / 100' : '—',
                'higher_is_better' => true,
            ],
            [
                'label' => 'Taxa de resposta',
                'hint' => 'Percentagem de reclamações que a empresa respondeu',
                'value' => fn (Company $c) => $c->response_rate,
                'format' => fn (?float $v) => $v !== null ? number_format($v, 0, ',', '').'%' : '—',
                'higher_is_better' => true,
            ],
            [
                'label' => 'Taxa de resolução',
                'hint' => 'Confirmada pelo consumidor, não declarada pela empresa',
                'value' => fn (Company $c) => $c->resolution_rate,
                'format' => fn (?float $v) => $v !== null ? number_format($v, 0, ',', '').'%' : '—',
                'higher_is_better' => true,
            ],
            [
                'label' => 'Tempo médio de resposta',
                'hint' => 'Desde a publicação até à primeira resposta',
                'value' => fn (Company $c) => $c->avg_first_response_minutes,
                'format' => fn (?int $v) => $v !== null ? self::humanDuration($v) : '—',
                'higher_is_better' => false,
            ],
            [
                'label' => 'Avaliação dos consumidores',
                'hint' => 'Média das avaliações de 1 a 5 após o desfecho',
                'value' => fn (Company $c) => $c->average_rating,
                'format' => fn (?float $v) => $v !== null ? number_format($v, 1, ',', '').' / 5' : '—',
                'higher_is_better' => true,
            ],
            [
                'label' => 'Reclamações publicadas',
                'hint' => 'Valor informativo: depende da dimensão da empresa',
                'value' => fn (Company $c) => $c->published_complaints_count,
                'format' => fn (?int $v) => number_format((int) $v, 0, ',', ' '),
                'higher_is_better' => null,
            ],
        ];
    }

    public static function humanDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' min';
        }

        if ($minutes < 1440) {
            return round($minutes / 60).' h';
        }

        $days = round($minutes / 1440, 1);

        return rtrim(rtrim(number_format($days, 1, ',', ''), '0'), ',').' dias';
    }
}
