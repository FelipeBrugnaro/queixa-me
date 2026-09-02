<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ratings\Enums\StatsPeriod;
use App\Domain\Ratings\Services\BrandAwardCalculator;
use App\Domain\Ratings\Services\CompanyStatsRecalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Recalcula indicadores e distinções.
 *
 * Pensado para correr no agendador: a janela de 12 meses todas as noites, os
 * snapshots mensais no primeiro dia de cada mês. Nada disto pode acontecer
 * durante um pedido HTTP.
 */
class RecalculateStats extends Command
{
    protected $signature = 'queixame:stats
        {--months=12 : Quantos meses de histórico recalcular}
        {--awards : Calcular também as Marcas do Mês}';

    protected $description = 'Recalcula os indicadores das empresas e, opcionalmente, as Marcas do Mês.';

    public function handle(CompanyStatsRecalculator $recalculator, BrandAwardCalculator $awards): int
    {
        $months = max(1, (int) $this->option('months'));

        $this->info('A recalcular janela de 12 meses…');
        $count = $recalculator->recalculateAll(StatsPeriod::Rolling12);
        $this->line("  {$count} empresas atualizadas.");

        $this->info("A recalcular {$months} snapshots mensais…");

        for ($i = 0; $i < $months; $i++) {
            $month = Carbon::now()->subMonths($i)->startOfMonth();
            $recalculator->recalculateAll(StatsPeriod::Monthly, $month);
            $this->line('  '.$month->format('Y-m').' concluído.');
        }

        if ($this->option('awards')) {
            $this->info('A calcular Marcas do Mês…');

            // O mês corrente ainda está incompleto: as distinções publicadas
            // referem-se sempre a meses fechados.
            for ($i = 1; $i <= min(6, $months); $i++) {
                $month = Carbon::now()->subMonths($i)->startOfMonth();
                $created = $awards->calculate($month);
                $this->line('  '.$month->format('Y-m').": {$created} distinções.");
            }
        }

        $this->newLine();
        $this->info('Concluído.');

        return self::SUCCESS;
    }
}
