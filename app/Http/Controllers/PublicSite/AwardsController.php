<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\Ratings\Models\BrandAward;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class AwardsController extends Controller
{
    public function index(): View
    {
        $latest = BrandAward::where('is_published', true)->max('period_start');

        return $this->render($latest ? Carbon::parse($latest) : Carbon::now()->startOfMonth(), canonicalToIndex: true);
    }

    public function period(string $period): View
    {
        [$year, $month] = array_map('intval', explode('-', $period));

        abort_if($month < 1 || $month > 12, 404);

        return $this->render(Carbon::create($year, $month, 1)->startOfMonth(), canonicalToIndex: false);
    }

    private function render(Carbon $period, bool $canonicalToIndex): View
    {
        $awards = BrandAward::where('is_published', true)
            ->whereDate('period_start', $period->toDateString())
            ->with('company:id,name,slug,logo_path,status,satisfaction_index,response_rate,resolution_rate,published_complaints_count,category_id')
            ->orderBy('award_type')
            ->orderBy('position')
            ->get()
            ->groupBy(fn (BrandAward $award) => $award->award_type->value);

        $availablePeriods = BrandAward::where('is_published', true)
            ->select('period_start')
            ->distinct()
            ->orderByDesc('period_start')
            ->limit(24)
            ->pluck('period_start');

        $label = $period->translatedFormat('F \d\e Y');

        $this->seo()
            ->title('Marcas do mês — '.$label)
            ->description('As empresas que mais se destacaram em '.$label.': melhor taxa de resposta, melhor taxa de resolução, melhor índice de satisfação e maior evolução.')
            ->canonical($canonicalToIndex ? route('awards') : route('awards.period', $period->format('Y-m')));

        $this->breadcrumbs([
            ['label' => 'Marcas do mês', 'url' => route('awards')],
            ...($canonicalToIndex ? [] : [['label' => $label, 'url' => null]]),
        ]);

        return view('public.awards', [
            'awards' => $awards,
            'period' => $period,
            'periodLabel' => $label,
            'availablePeriods' => $availablePeriods,
        ]);
    }
}
