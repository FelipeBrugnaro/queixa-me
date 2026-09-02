<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Enums;

use App\Domain\Shared\Concerns\HasLabel;

/**
 * EIXO 1 - Estado editorial da reclamacao.
 *
 * Foi separado do ciclo de vida publico (ver ComplaintStage) porque misturar
 * "em analise" com "empresa respondeu" torna impossivel representar situacoes
 * reais como: uma reclamacao ja publicada que sofre uma atualizacao e volta a
 * moderacao sem sair do ar, ou uma reclamacao resolvida que e depois removida
 * por decisao de moderacao.
 */
enum ModerationStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Submitted => 'Submetida',
            self::InReview => 'Em análise',
            self::ChangesRequested => 'Necessita de alterações',
            self::Approved => 'Aprovada',
            self::Rejected => 'Rejeitada',
            self::Removed => 'Removida',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::Submitted, self::InReview => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::ChangesRequested => 'bg-orange-50 text-orange-800 ring-orange-200',
            self::Approved => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            self::Rejected, self::Removed => 'bg-rose-50 text-rose-800 ring-rose-200',
        };
    }

    public function isEditableByAuthor(): bool
    {
        return in_array($this, [self::Draft, self::ChangesRequested], true);
    }

    public function isPending(): bool
    {
        return in_array($this, [self::Submitted, self::InReview], true);
    }

    public function isPublic(): bool
    {
        return $this === self::Approved;
    }
}
