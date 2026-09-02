<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Enums;

use App\Domain\Shared\Concerns\HasLabel;

/**
 * Tipo base da conta. A separação entre consumidor e empresa existe porque os
 * dois perfis têm ciclos de vida, dados obrigatórios e permissões distintas.
 */
enum UserType: string
{
    use HasLabel;

    case Consumer = 'consumer';
    case Business = 'business';
    case Moderator = 'moderator';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Consumer => 'Consumidor',
            self::Business => 'Empresa',
            self::Moderator => 'Moderador',
            self::Admin => 'Administrador',
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::Moderator, self::Admin], true);
    }
}
