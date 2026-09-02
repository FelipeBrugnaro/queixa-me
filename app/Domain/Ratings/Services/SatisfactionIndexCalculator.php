<?php

declare(strict_types=1);

namespace App\Domain\Ratings\Services;

/**
 * METODOLOGIA DO INDICE DE SATISFACAO
 *
 * Problema
 * --------
 * Ordenar empresas pelo numero de reclamacoes e enganador: um retalhista com
 * milhoes de clientes recebera sempre mais reclamacoes do que uma loja de
 * bairro, mesmo servindo melhor. E ordenar por medias simples e instavel: uma
 * empresa com uma unica reclamacao resolvida ficaria em primeiro lugar com
 * 100%, a frente de outra com 4000 reclamacoes e 92% de resolucao.
 *
 * Solucao
 * -------
 * 1. Medir COMPORTAMENTO (responde? resolve? satisfaz? e rapida?), nunca
 *    volume absoluto. Todas as componentes sao taxas, nao contagens.
 * 2. Suavizacao bayesiana: cada empresa recebe M "reclamacoes virtuais" com o
 *    valor medio do mercado. Com poucos dados o indice aproxima-se da media;
 *    com muitos dados, o peso do proprio historico domina. Isto elimina os
 *    extremos artificiais sem penalizar quem tem pouco volume.
 * 3. Janela movel de 12 meses: mede a empresa que existe hoje, nao a de 2019.
 * 4. Minimo de reclamacoes para constar do ranking publico.
 *
 * O indice bruto (sem suavizacao) e publicado lado a lado com o indice final
 * para que qualquer empresa possa verificar o calculo.
 */
class SatisfactionIndexCalculator
{
    /**
     * @param  array{answerable:int,replied:int,resolved:int,rated:int,rating_sum:float,response_minutes:array<int,int>}  $metrics
     * @return array<string,mixed>
     */
    public function compute(array $metrics, ?float $marketPrior = null): array
    {
        $weights = (array) config('queixame.index.weights');
        $scale = (float) config('queixame.index.scale');

        $answerable = max(0, (int) $metrics['answerable']);
        $replied = max(0, (int) $metrics['replied']);
        $resolved = max(0, (int) $metrics['resolved']);
        $rated = max(0, (int) $metrics['rated']);
        $ratingSum = (float) $metrics['rating_sum'];
        $responseMinutes = $metrics['response_minutes'] ?? [];

        // Componentes normalizadas para 0..1.
        $responseRate = $answerable > 0 ? $replied / $answerable : null;
        $resolutionRate = $answerable > 0 ? $resolved / $answerable : null;
        $averageRating = $rated > 0 ? $ratingSum / $rated : null;
        $satisfaction = $averageRating !== null ? ($averageRating - 1) / 4 : null;

        $avgMinutes = $responseMinutes !== [] ? (int) round(array_sum($responseMinutes) / count($responseMinutes)) : null;
        $speed = $this->speedScore($avgMinutes);

        // Componentes sem dados nao penalizam: o peso e redistribuido pelas
        // restantes. Uma empresa sem avaliacoes nao deve ser tratada como se
        // tivesse avaliacoes maximas nem minimas.
        $components = [
            'response_rate' => $responseRate,
            'resolution_rate' => $resolutionRate,
            'satisfaction' => $satisfaction,
            'speed' => $speed,
        ];

        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($components as $key => $value) {
            if ($value === null) {
                continue;
            }

            $weight = (float) ($weights[$key] ?? 0);
            $weightedSum += $value * $weight;
            $weightTotal += $weight;
        }

        $rawIndex = $weightTotal > 0 ? ($weightedSum / $weightTotal) * $scale : null;

        return [
            'response_rate' => $this->percent($responseRate),
            'resolution_rate' => $this->percent($resolutionRate),
            'average_rating' => $averageRating !== null ? round($averageRating, 2) : null,
            'avg_first_response_minutes' => $avgMinutes,
            'median_first_response_minutes' => $this->median($responseMinutes),
            'speed_score' => $speed !== null ? round($speed * $scale, 2) : null,
            'raw_index' => $rawIndex !== null ? round($rawIndex, 2) : null,
            'satisfaction_index' => $this->applyBayesianShrinkage($rawIndex, $answerable, $marketPrior),
            'breakdown' => [
                'components' => array_map(
                    fn (?float $value) => $value !== null ? round($value * $scale, 2) : null,
                    $components
                ),
                'weights' => $weights,
                'sample_size' => $answerable,
                'market_prior' => $marketPrior !== null ? round($marketPrior, 2) : null,
                'prior_weight' => (int) config('queixame.index.bayesian_prior_weight'),
            ],
        ];
    }

    /**
     * indice_final = (n * indice_bruto + M * media_do_mercado) / (n + M)
     *
     * n  = numero de reclamacoes com oportunidade de resposta na janela
     * M  = constante de suavizacao (queixame.index.bayesian_prior_weight)
     */
    public function applyBayesianShrinkage(?float $rawIndex, int $sampleSize, ?float $marketPrior): ?float
    {
        if ($rawIndex === null) {
            return null;
        }

        if ($marketPrior === null || $sampleSize <= 0) {
            return round($rawIndex, 2);
        }

        $priorWeight = (float) config('queixame.index.bayesian_prior_weight');

        $value = (($sampleSize * $rawIndex) + ($priorWeight * $marketPrior)) / ($sampleSize + $priorWeight);

        return round($value, 2);
    }

    /**
     * Velocidade normalizada: 1.0 ate ao limite "bom", 0.0 a partir do limite
     * "mau", linear entre os dois. Uma escala linear pura seria dominada por
     * casos extremos (uma resposta ao fim de 6 meses arrasaria a media).
     */
    private function speedScore(?int $minutes): ?float
    {
        if ($minutes === null) {
            return null;
        }

        $best = (float) config('queixame.index.speed_best_hours') * 60;
        $worst = (float) config('queixame.index.speed_worst_hours') * 60;

        if ($minutes <= $best) {
            return 1.0;
        }

        if ($minutes >= $worst) {
            return 0.0;
        }

        return 1.0 - (($minutes - $best) / ($worst - $best));
    }

    private function percent(?float $ratio): ?float
    {
        return $ratio !== null ? round($ratio * 100, 2) : null;
    }

    /** @param array<int,int> $values */
    private function median(array $values): ?int
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 0
            ? (int) round(($values[$middle - 1] + $values[$middle]) / 2)
            : $values[$middle];
    }
}
