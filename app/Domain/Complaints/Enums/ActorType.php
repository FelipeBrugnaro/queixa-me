<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Enums;

use App\Domain\Shared\Concerns\HasLabel;

enum ActorType: string
{
    use HasLabel;

    case Consumer = 'consumer';
    case Company = 'company';
    case Moderator = 'moderator';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Consumer => 'Consumidor',
            self::Company => 'Empresa',
            self::Moderator => 'Moderação',
            self::System => 'Sistema',
        };
    }
}
