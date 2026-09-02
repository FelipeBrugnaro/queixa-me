<?php

declare(strict_types=1);

namespace App\Domain\Ratings\Enums;

use App\Domain\Shared\Concerns\HasLabel;

enum AwardType: string
{
    use HasLabel;

    case BrandOfTheMonth = 'brand_of_the_month';
    case BestResponse = 'best_response';
    case BestResolution = 'best_resolution';
    case BestSatisfaction = 'best_satisfaction';
    case BestImprovement = 'best_improvement';
    case BestService = 'best_service';

    public function label(): string
    {
        return match ($this) {
            self::BrandOfTheMonth => 'Marca do mês',
            self::BestResponse => 'Melhor taxa de resposta',
            self::BestResolution => 'Melhor taxa de resolução',
            self::BestSatisfaction => 'Melhor índice de satisfação',
            self::BestImprovement => 'Maior evolução',
            self::BestService => 'Melhor atendimento',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::BrandOfTheMonth => 'Melhor desempenho global do mês, combinando resposta, resolução e satisfação.',
            self::BestResponse => 'Empresa que respondeu à maior percentagem de reclamações recebidas no mês.',
            self::BestResolution => 'Empresa com a maior percentagem de reclamações confirmadas como resolvidas pelos consumidores.',
            self::BestSatisfaction => 'Empresa com a melhor avaliação média atribuída pelos consumidores após o desfecho.',
            self::BestImprovement => 'Empresa cujo índice de satisfação mais subiu face aos três meses anteriores.',
            self::BestService => 'Empresa com o menor tempo médio até à primeira resposta.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BrandOfTheMonth => 'trophy',
            self::BestResponse => 'reply',
            self::BestResolution => 'check',
            self::BestSatisfaction => 'star',
            self::BestImprovement => 'trend',
            self::BestService => 'clock',
        };
    }
}
