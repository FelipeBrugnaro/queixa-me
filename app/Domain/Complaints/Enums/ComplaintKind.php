<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Enums;

use App\Domain\Shared\Concerns\HasLabel;

/**
 * Tipo de reclamacao. As reclamacoes laborais seguem um circuito de moderacao
 * mais exigente e NAO contam para os indices comerciais das empresas, porque
 * medem uma realidade diferente da relacao de consumo.
 */
enum ComplaintKind: string
{
    use HasLabel;

    case Consumer = 'consumer';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Consumer => 'Consumo',
            self::Employee => 'Colaborador / ex-colaborador',
        };
    }

    public function countsTowardsIndex(): bool
    {
        return $this === self::Consumer;
    }
}
