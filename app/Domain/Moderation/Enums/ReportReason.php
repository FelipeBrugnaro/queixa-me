<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Enums;

use App\Domain\Shared\Concerns\HasLabel;

enum ReportReason: string
{
    use HasLabel;

    case FalseInformation = 'false_information';
    case PersonalData = 'personal_data';
    case Offensive = 'offensive';
    case Spam = 'spam';
    case Impersonation = 'impersonation';
    case Copyright = 'copyright';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FalseInformation => 'Informação falsa ou enganosa',
            self::PersonalData => 'Expõe dados pessoais',
            self::Offensive => 'Conteúdo ofensivo',
            self::Spam => 'Spam',
            self::Impersonation => 'Faz-se passar por outra pessoa ou entidade',
            self::Copyright => 'Violação de direitos de autor',
            self::Other => 'Outro motivo',
        };
    }
}
