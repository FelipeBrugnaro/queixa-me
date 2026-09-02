<?php

declare(strict_types=1);

namespace App\Domain\Companies\Enums;

use App\Domain\Shared\Concerns\HasLabel;

/**
 * Ciclo de vida da ficha de empresa. Empresas criadas por utilizadores nascem
 * em "pending" e so ficam indexaveis depois de validadas, o que evita
 * duplicados, spam e fichas difamatorias criadas com nomes inventados.
 */
enum CompanyStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Active = 'active';
    case Verified = 'verified';
    case Merged = 'merged';
    case Suspended = 'suspended';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Por validar',
            self::Active => 'Ativa',
            self::Verified => 'Verificada',
            self::Merged => 'Fundida',
            self::Suspended => 'Suspensa',
            self::Rejected => 'Rejeitada',
        };
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this, [self::Active, self::Verified], true);
    }
}
