<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Enums;

use App\Domain\Shared\Concerns\HasLabel;

/**
 * EIXO 2 - Ciclo de vida publico da reclamacao, apos aprovacao.
 * E este eixo que alimenta os indicadores das empresas.
 */
enum ComplaintStage: string
{
    use HasLabel;

    case NotPublished = 'not_published';
    case Published = 'published';
    case CompanyNotified = 'company_notified';
    case CompanyReplied = 'company_replied';
    case InFollowUp = 'in_follow_up';
    case Resolved = 'resolved';
    case Unresolved = 'unresolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::NotPublished => 'Por publicar',
            self::Published => 'Publicada',
            self::CompanyNotified => 'Empresa notificada',
            self::CompanyReplied => 'Empresa respondeu',
            self::InFollowUp => 'Em acompanhamento',
            self::Resolved => 'Resolvida',
            self::Unresolved => 'Não resolvida',
            self::Closed => 'Encerrada',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::NotPublished => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::Published, self::CompanyNotified => 'bg-sky-50 text-sky-800 ring-sky-200',
            self::CompanyReplied, self::InFollowUp => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
            self::Resolved => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            self::Unresolved => 'bg-rose-50 text-rose-800 ring-rose-200',
            self::Closed => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    public function hasCompanyReply(): bool
    {
        return in_array($this, [
            self::CompanyReplied,
            self::InFollowUp,
            self::Resolved,
            self::Unresolved,
            self::Closed,
        ], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Resolved, self::Unresolved, self::Closed], true);
    }

    /** @return array<int,self> */
    public static function publicFilterable(): array
    {
        return [
            self::Published,
            self::CompanyReplied,
            self::InFollowUp,
            self::Resolved,
            self::Unresolved,
            self::Closed,
        ];
    }
}
