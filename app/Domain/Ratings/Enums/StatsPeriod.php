<?php

declare(strict_types=1);

namespace App\Domain\Ratings\Enums;

use App\Domain\Shared\Concerns\HasLabel;

/**
 * Janelas de agregacao dos indicadores.
 *
 * O ranking publico usa Rolling12 por omissao: uma empresa nao deve ficar
 * marcada para sempre por um mau semestre, nem deve poder "limpar" o
 * historico com um mes bom. Monthly serve as Marcas do Mes e aos graficos
 * de evolucao; AllTime serve apenas para contagens informativas.
 */
enum StatsPeriod: string
{
    use HasLabel;

    case Monthly = 'monthly';
    case Rolling12 = 'rolling12';
    case AllTime = 'all_time';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Mensal',
            self::Rolling12 => 'Últimos 12 meses',
            self::AllTime => 'Desde sempre',
        };
    }
}
