<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Enums;

use App\Domain\Shared\Concerns\HasLabel;

enum ReportStatus: string
{
    use HasLabel;

    case Open = 'open';
    case InReview = 'in_review';
    case Upheld = 'upheld';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Em aberto',
            self::InReview => 'Em análise',
            self::Upheld => 'Procedente',
            self::Dismissed => 'Improcedente',
        };
    }
}
