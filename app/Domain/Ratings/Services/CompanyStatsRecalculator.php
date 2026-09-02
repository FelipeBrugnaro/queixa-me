<?php

declare(strict_types=1);

namespace App\Domain\Ratings\Services;

use App\Domain\Companies\Enums\CompanyStatus;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Enums\ComplaintKind;
use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Enums\ModerationStatus;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Ratings\Enums\StatsPeriod;
use App\Domain\Ratings\Models\CompanyStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Recalcula os indicadores das empresas e grava snapshots.
 *
 * Corre em fila (job agendado), nunca durante um pedido HTTP: com centenas de
 * milhares de reclamacoes, calcular indices ao vivo tornaria cada pagina de
 * empresa numa varredura de tabela.
 */
class CompanyStatsRecalculator
{
    public function __construct(private readonly SatisfactionIndexCalculator $calculator) {}

    /** Recalcula uma janela para todas as empresas publicas. */
    public function recalculateAll(StatsPeriod $period, ?Carbon $reference = null): int
    {
        $reference ??= Carbon::now();
        [$start, $end] = $this->window($period, $reference);

        // O prior de mercado tem de ser calculado antes das empresas, porque
        // e ele que ancora a suavizacao bayesiana de cada uma.
        $marketPrior = $this->marketPrior($start, $end);

        $count = 0;

        Company::query()
            ->whereIn('status', [CompanyStatus::Active->value, CompanyStatus::Verified->value])
            ->chunkById(200, function (Collection $companies) use ($period, $start, $end, $marketPrior, &$count): void {
                foreach ($companies as $company) {
                    $this->recalculateCompany($company, $period, $start, $end, $marketPrior);
                    $count++;
                }
            });

        if ($period === StatsPeriod::Rolling12) {
            $this->assignRanks($period, $start);
        }

        return $count;
    }

    public function recalculateCompany(
        Company $company,
        StatsPeriod $period,
        Carbon $start,
        Carbon $end,
        ?float $marketPrior = null,
    ): CompanyStat {
        $metrics = $this->collectMetrics($company->id, $start, $end);
        $computed = $this->calculator->compute($metrics, $marketPrior);

        $previous = CompanyStat::where('company_id', $company->id)
            ->where('period_type', $period->value)
            ->where('period_start', '<', $start->toDateString())
            ->orderByDesc('period_start')
            ->first();

        $stat = CompanyStat::updateOrCreate(
            [
                'company_id' => $company->id,
                'period_type' => $period->value,
                'period_start' => $start->toDateString(),
            ],
            [
                'period_end' => $end->toDateString(),
                'complaints_count' => $metrics['total'],
                'answerable_count' => $metrics['answerable'],
                'replied_count' => $metrics['replied'],
                'resolved_count' => $metrics['resolved'],
                'unresolved_count' => $metrics['unresolved'],
                'rated_count' => $metrics['rated'],
                'response_rate' => $computed['response_rate'],
                'resolution_rate' => $computed['resolution_rate'],
                'average_rating' => $computed['average_rating'],
                'would_recommend_rate' => $metrics['recommend_total'] > 0
                    ? round($metrics['recommend_yes'] / $metrics['recommend_total'] * 100, 2)
                    : null,
                'avg_first_response_minutes' => $computed['avg_first_response_minutes'],
                'median_first_response_minutes' => $computed['median_first_response_minutes'],
                'speed_score' => $computed['speed_score'],
                'satisfaction_index' => $computed['satisfaction_index'],
                'raw_index' => $computed['raw_index'],
                'is_ranked' => $metrics['total'] >= (int) config('queixame.index.ranking_minimum_complaints')
                    && $computed['satisfaction_index'] !== null,
                'previous_index' => $previous?->satisfaction_index,
                'index_delta' => $previous?->satisfaction_index !== null && $computed['satisfaction_index'] !== null
                    ? round($computed['satisfaction_index'] - $previous->satisfaction_index, 2)
                    : null,
                'breakdown' => $computed['breakdown'],
                'computed_at' => now(),
            ],
        );

        // A janela de 12 meses e a que representa a empresa nas paginas
        // publicas, por isso e a unica que se projeta na tabela companies.
        if ($period === StatsPeriod::Rolling12) {
            $company->forceFill([
                'satisfaction_index' => $computed['satisfaction_index'],
                'response_rate' => $computed['response_rate'],
                'resolution_rate' => $computed['resolution_rate'],
                'average_rating' => $computed['average_rating'],
                'avg_first_response_minutes' => $computed['avg_first_response_minutes'],
                'published_complaints_count' => $this->publishedCount($company->id),
                'replied_complaints_count' => $metrics['replied'],
                'resolved_complaints_count' => $metrics['resolved'],
                'complaints_count' => Complaint::where('company_id', $company->id)->count(),
                'is_indexable' => $company->shouldBeIndexed(),
            ])->saveQuietly();
        }

        return $stat;
    }

    /**
     * @return array<string,mixed>
     */
    private function collectMetrics(int $companyId, Carbon $start, Carbon $end): array
    {
        $slaDays = (int) config('queixame.complaints.response_sla_days');
        $slaCutoff = Carbon::now()->subDays($slaDays);

        $metrics = [
            'total' => 0,
            'answerable' => 0,
            'replied' => 0,
            'resolved' => 0,
            'unresolved' => 0,
            'rated' => 0,
            'rating_sum' => 0.0,
            'recommend_total' => 0,
            'recommend_yes' => 0,
            'response_minutes' => [],
        ];

        Complaint::query()
            ->where('company_id', $companyId)
            ->where('kind', ComplaintKind::Consumer->value)
            ->where('moderation_status', ModerationStatus::Approved->value)
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end])
            ->select([
                'id', 'published_at', 'first_response_at', 'stage',
                'rating', 'would_recommend',
            ])
            ->chunkById(500, function (Collection $complaints) use (&$metrics, $slaCutoff): void {
                foreach ($complaints as $complaint) {
                    $metrics['total']++;

                    $hasReply = $complaint->first_response_at !== null;

                    // Uma reclamacao publicada ha dois dias ainda esta dentro
                    // do prazo: nao pode contar como "sem resposta".
                    $isAnswerable = $hasReply || $complaint->published_at->lessThanOrEqualTo($slaCutoff);

                    if (! $isAnswerable) {
                        continue;
                    }

                    $metrics['answerable']++;

                    if ($hasReply) {
                        $metrics['replied']++;
                        $metrics['response_minutes'][] = (int) $complaint->published_at
                            ->diffInMinutes($complaint->first_response_at);
                    }

                    if ($complaint->stage === ComplaintStage::Resolved) {
                        $metrics['resolved']++;
                    }

                    if ($complaint->stage === ComplaintStage::Unresolved) {
                        $metrics['unresolved']++;
                    }

                    if ($complaint->rating !== null) {
                        $metrics['rated']++;
                        $metrics['rating_sum'] += (float) $complaint->rating;
                    }

                    if ($complaint->would_recommend !== null) {
                        $metrics['recommend_total']++;
                        $metrics['recommend_yes'] += $complaint->would_recommend ? 1 : 0;
                    }
                }
            });

        return $metrics;
    }

    /**
     * Media de mercado dos indices brutos, ponderada pela dimensao de cada
     * empresa. E o valor para o qual empresas com pouca amostra convergem.
     */
    private function marketPrior(Carbon $start, Carbon $end): ?float
    {
        $totals = ['answerable' => 0, 'replied' => 0, 'resolved' => 0, 'rated' => 0, 'rating_sum' => 0.0, 'response_minutes' => []];

        Complaint::query()
            ->whereNotNull('company_id')
            ->where('kind', ComplaintKind::Consumer->value)
            ->where('moderation_status', ModerationStatus::Approved->value)
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end])
            ->select(['id', 'published_at', 'first_response_at', 'stage', 'rating'])
            ->chunkById(1000, function (Collection $complaints) use (&$totals): void {
                $slaCutoff = Carbon::now()->subDays((int) config('queixame.complaints.response_sla_days'));

                foreach ($complaints as $complaint) {
                    $hasReply = $complaint->first_response_at !== null;

                    if (! $hasReply && $complaint->published_at->greaterThan($slaCutoff)) {
                        continue;
                    }

                    $totals['answerable']++;

                    if ($hasReply) {
                        $totals['replied']++;
                        $totals['response_minutes'][] = (int) $complaint->published_at
                            ->diffInMinutes($complaint->first_response_at);
                    }

                    if ($complaint->stage === ComplaintStage::Resolved) {
                        $totals['resolved']++;
                    }

                    if ($complaint->rating !== null) {
                        $totals['rated']++;
                        $totals['rating_sum'] += (float) $complaint->rating;
                    }
                }
            });

        if ($totals['answerable'] === 0) {
            return null;
        }

        return $this->calculator->compute($totals + ['total' => $totals['answerable']])['raw_index'];
    }

    private function assignRanks(StatsPeriod $period, Carbon $start): void
    {
        $position = 0;

        CompanyStat::query()
            ->where('period_type', $period->value)
            ->where('period_start', $start->toDateString())
            ->where('is_ranked', true)
            ->orderByDesc('satisfaction_index')
            ->orderByDesc('complaints_count')
            ->chunkById(500, function (Collection $stats) use (&$position): void {
                foreach ($stats as $stat) {
                    $position++;
                    $stat->forceFill(['rank_overall' => $position])->saveQuietly();
                }
            });
    }

    private function publishedCount(int $companyId): int
    {
        return Complaint::where('company_id', $companyId)
            ->where('moderation_status', ModerationStatus::Approved->value)
            ->whereNotNull('published_at')
            ->count();
    }

    /** @return array{0:Carbon,1:Carbon} */
    public function window(StatsPeriod $period, Carbon $reference): array
    {
        return match ($period) {
            StatsPeriod::Monthly => [
                $reference->copy()->startOfMonth(),
                $reference->copy()->endOfMonth(),
            ],
            StatsPeriod::Rolling12 => [
                $reference->copy()->subMonths(12)->startOfDay(),
                $reference->copy()->endOfDay(),
            ],
            StatsPeriod::AllTime => [
                Carbon::create(2020, 1, 1)->startOfDay(),
                $reference->copy()->endOfDay(),
            ],
        };
    }
}
