<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Enums;

use App\Domain\Shared\Concerns\HasLabel;

enum Gender: string
{
    use HasLabel;

    case Female = 'female';
    case Male = 'male';
    case Other = 'other';
    case Undisclosed = 'undisclosed';

    public function label(): string
    {
        return match ($this) {
            self::Female => 'Feminino',
            self::Male => 'Masculino',
            self::Other => 'Outro',
            self::Undisclosed => 'Prefiro não indicar',
        };
    }
}
