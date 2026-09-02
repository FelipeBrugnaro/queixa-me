<?php

declare(strict_types=1);

namespace App\Domain\Ratings\Services;

use App\Domain\Ratings\Enums\AwardType;
use App\Domain\Ratings\Enums\StatsPeriod;
use App\Domain\Ratings\Models\BrandAward;
use App\Domain\Ratings\Models\CompanyStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Marcas do Mês.
 *
 * As distinções são calculadas a partir dos mesmos indicadores públicos que
 * alimentam o ranking, com um limiar mínimo de reclamações no mês. Sem esse
 * limiar, "melhor taxa de resposta do mês" seria sempre uma empresa com uma
 * única reclamação respondida — o que premiaria a irrelevância estatística
 * e retiraria credibilidade à secção inteira.
 *
 * A equipa editorial pode sobrepor-se ao cálculo (is_editorial), mas a
 * distinção fica marcada como tal, para que a leitura seja honesta.
 */
class BrandAwardCalculator
{
    public function calculate(Carbon $month, bool $publish = true): int
    {
        $month = $month->copy()->startOfMonth();
        $minimum = (int) config('queixame.awards.minimum_complaints');

        $stats = CompanyStat::query()
            ->where('period_type', StatsPeriod::Monthly->value)
            ->whereDate('period_start', $month->toDateString())
            ->where('complaints_count', '>=', $minimum)
            ->with('company:id,name,category_id,status')
            ->get()
            ->filter(fn (CompanyStat $stat) => $stat->company?->isPublic());

        if ($stats->isEmpty()) {
            return 0;
        }

        BrandAward::whereDate('period_start', $month->toDateString())
            ->where('is_editorial', false)
            ->delete();

        $awards = [
            [AwardType::BrandOfTheMonth, $this->best($stats, fn (CompanyStat $s) => $s->satisfaction_index)],
            [AwardType::BestResponse, $this->best($stats, fn (CompanyStat $s) => $s->response_rate)],
            [AwardType::BestResolution, $this->best($stats, fn (CompanyStat $s) => $s->resolution_rate)],
            [AwardType::BestSatisfaction, $this->best($stats, fn (CompanyStat $s) => $s->average_rating)],
            [AwardType::BestImprovement, $this->best($stats, fn (CompanyStat $s) => $s->index_delta)],
            // Rapidez: aqui menor é melhor, daí a inversão do sinal.
            [AwardType::BestService, $this->best(
                $stats->filter(fn (CompanyStat $s) => $s->avg_first_response_minutes !== null),
                fn (CompanyStat $s) => -$s->avg_first_response_minutes,
            )],
        ];

        $created = 0;

        foreach ($awards as [$type, $stat]) {
            if ($stat === null) {
                continue;
            }

            BrandAward::updateOrCreate(
                [
                    'award_type' => $type->value,
                    'period_start' => $month->toDateString(),
                    'position' => 1,
                    'category_id' => null,
                ],
                [
                    'company_id' => $stat->company_id,
                    'metric_value' => $this->metricFor($type, $stat),
                    'is_editorial' => false,
                    'is_published' => $publish,
                ],
            );

            $created++;
        }

        return $created;
    }

    /** @param Collection<int,CompanyStat> $stats */
    private function best(Collection $stats, callable $metric): ?CompanyStat
    {
        return $stats
            ->filter(fn (CompanyStat $stat) => $metric($stat) !== null)
            ->sortByDesc($metric)
            ->first();
    }

    private function metricFor(AwardType $type, CompanyStat $stat): ?float
    {
        return match ($type) {
            AwardType::BrandOfTheMonth => $stat->satisfaction_index,
            AwardType::BestResponse => $stat->response_rate,
            AwardType::BestResolution => $stat->resolution_rate,
            AwardType::BestSatisfaction => $stat->average_rating,
            AwardType::BestImprovement => $stat->index_delta,
            AwardType::BestService => $stat->avg_first_response_minutes !== null
                ? round($stat->avg_first_response_minutes / 60, 1)
                : null,
        };
    }
}
