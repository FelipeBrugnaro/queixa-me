<?php

declare(strict_types=1);

namespace App\Domain\Companies\Enums;

use App\Domain\Shared\Concerns\HasLabel;

enum CompanyRole: string
{
    use HasLabel;

    case Owner = 'owner';
    case Manager = 'manager';
    case Agent = 'agent';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Proprietário da conta',
            self::Manager => 'Gestor',
            self::Agent => 'Agente de atendimento',
        };
    }

    /** @return array<int,string> */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => ['company.manage', 'company.users', 'complaints.reply', 'complaints.view', 'messages.send', 'stats.view'],
            self::Manager => ['company.manage', 'complaints.reply', 'complaints.view', 'messages.send', 'stats.view'],
            self::Agent => ['complaints.reply', 'complaints.view', 'messages.send'],
        };
    }
}
