<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Ratings\Enums\StatsPeriod;
use App\Domain\Ratings\Services\BrandAwardCalculator;
use App\Domain\Ratings\Services\CompanyStatsRecalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Conteúdo estrutural: existe em qualquer ambiente, incluindo produção.
        $this->call([
            CompanyCategorySeeder::class,
            FaqSeeder::class,
            LegalDocumentSeeder::class,
        ]);

        // Dados de demonstração: apenas fora de produção.
        if (app()->isProduction()) {
            return;
        }

        $this->call([
            DemoDataSeeder::class,
            PostSeeder::class,
        ]);

        $this->command?->info('A calcular indicadores…');

        $recalculator = app(CompanyStatsRecalculator::class);
        $recalculator->recalculateAll(StatsPeriod::Rolling12);

        for ($i = 0; $i < 12; $i++) {
            $recalculator->recalculateAll(StatsPeriod::Monthly, Carbon::now()->subMonths($i));
        }

        $awards = app(BrandAwardCalculator::class);

        for ($i = 1; $i <= 3; $i++) {
            $awards->calculate(Carbon::now()->subMonths($i));
        }

        $this->command?->info('Portal de demonstração pronto.');
        $this->command?->line('  admin@queixa.me / password');
        $this->command?->line('  moderador@queixa.me / password');
    }
}
