<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

use App\Domain\Shared\Concerns\HasLabel;

enum PostStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Scheduled => 'Agendado',
            self::Published => 'Publicado',
            self::Archived => 'Arquivado',
        };
    }
}
