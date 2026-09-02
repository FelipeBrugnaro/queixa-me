<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Enums;

use App\Domain\Shared\Concerns\HasLabel;

enum UserStatus: string
{
    use HasLabel;

    case Active = 'active';
    case Suspended = 'suspended';
    case Blocked = 'blocked';
    /** Conta anonimizada a pedido do titular (RGPD art. 17). */
    case Anonymised = 'anonymised';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::Suspended => 'Suspensa',
            self::Blocked => 'Bloqueada',
            self::Anonymised => 'Anonimizada',
        };
    }

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
