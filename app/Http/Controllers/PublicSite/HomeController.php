<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyCategory;
use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Content\Models\Post;
use App\Domain\Ratings\Enums\AwardType;
use App\Domain\Ratings\Models\BrandAward;
use App\Domain\Seo\Services\SchemaBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $this->seo()
            ->title('Reclamações, respostas e resoluções')
            ->description('Apresenta a tua reclamação, acompanha a resposta da empresa e consulta o historial de milhares de marcas antes de comprar. Portal independente de reclamações de consumo.')
            ->canonical(route('home'))
            ->schema(SchemaBuilder::organization())
            ->schema(SchemaBuilder::website());

        // Só os agregados vão para cache. As contagens da plataforma são as
        // consultas caras (varrem toda a tabela de reclamações) e mudam
        // devagar; as listagens são pequenas, indexadas e limitadas, e vale
        // mais mostrá-las sempre frescas. Em produção, a poupança relevante
        // vem de cache HTTP à frente da aplicação, não de guardar modelos
        // Eloquent serializados.
        $stats = Cache::remember('home:stats', now()->addMinutes(15), fn () => $this->platformStats());

        return view('public.home', [
            'stats' => $stats,
            'recentComplaints' => $this->recentComplaints(),
            'answeredComplaints' => $this->answeredComplaints(),
            'topCompanies' => $this->topCompanies(),
            'awards' => $this->currentAwards(),
            'posts' => $this->latestPosts(),
            'categories' => $this->categories(),
        ]);
    }

    /** @return array<string,int|float|null> */
    private function platformStats(): array
    {
        $published = Complaint::published();

        $total = (clone $published)->count();
        $replied = (clone $published)->whereNotNull('first_response_at')->count();
        $resolved = (clone $published)->where('stage', ComplaintStage::Resolved->value)->count();

        return [
            'complaints' => $total,
            'companies' => Company::public()->count(),
            'response_rate' => $total > 0 ? round($replied / $total * 100) : null,
            'resolution_rate' => $total > 0 ? round($resolved / $total * 100) : null,
        ];
    }

    private function recentComplaints()
    {
        return Complaint::published()
            ->forCards()
            ->latest('published_at')
            ->limit(6)
            ->get();
    }

    private function answeredComplaints()
    {
        return Complaint::published()
            ->where('stage', ComplaintStage::Resolved->value)
            ->forCards()
            ->latest('resolved_at')
            ->limit(3)
            ->get();
    }

    private function topCompanies()
    {
        return Company::rankable()
            ->with('category:id,name,slug')
            ->orderByDesc('satisfaction_index')
            ->limit(6)
            ->get();
    }

    private function currentAwards()
    {
        return BrandAward::query()
            ->where('is_published', true)
            ->whereIn('award_type', [
                AwardType::BrandOfTheMonth->value,
                AwardType::BestResponse->value,
                AwardType::BestResolution->value,
                AwardType::BestSatisfaction->value,
            ])
            ->with('company:id,name,slug,logo_path,status,satisfaction_index,published_complaints_count,response_rate,resolution_rate')
            ->orderByDesc('period_start')
            ->orderBy('position')
            ->limit(4)
            ->get()
            ->unique('award_type');
    }

    private function latestPosts()
    {
        return Post::published()
            ->with('category:id,name,slug')
            ->latest('published_at')
            ->limit(3)
            ->get();
    }

    private function categories()
    {
        return CompanyCategory::query()
            ->whereNull('parent_id')
            ->withCount(['companies' => fn ($q) => $q->public()])
            ->orderBy('position')
            ->limit(12)
            ->get();
    }
}
