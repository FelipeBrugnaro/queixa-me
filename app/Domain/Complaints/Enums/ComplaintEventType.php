<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Enums;

use App\Domain\Shared\Concerns\HasLabel;

/**
 * Eventos da timeline. Os eventos com is_public = true aparecem na pagina
 * publica da reclamacao; os restantes ficam apenas na area do autor,
 * da empresa ou da moderacao.
 */
enum ComplaintEventType: string
{
    use HasLabel;

    case Created = 'created';
    case Submitted = 'submitted';
    case ReviewStarted = 'review_started';
    case ChangesRequested = 'changes_requested';
    case Resubmitted = 'resubmitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case CompanyNotified = 'company_notified';
    case CompanyReplied = 'company_replied';
    case ConsumerReplied = 'consumer_replied';
    case Updated = 'updated';
    case ResolutionProposed = 'resolution_proposed';
    case Resolved = 'resolved';
    case MarkedUnresolved = 'marked_unresolved';
    case Rated = 'rated';
    case Closed = 'closed';
    case Reopened = 'reopened';
    case Reported = 'reported';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Reclamação criada',
            self::Submitted => 'Reclamação submetida',
            self::ReviewStarted => 'Análise iniciada',
            self::ChangesRequested => 'Foram pedidas alterações',
            self::Resubmitted => 'Reclamação reenviada',
            self::Approved => 'Reclamação aprovada',
            self::Rejected => 'Reclamação rejeitada',
            self::Published => 'Reclamação publicada',
            self::CompanyNotified => 'Empresa notificada',
            self::CompanyReplied => 'A empresa respondeu',
            self::ConsumerReplied => 'O consumidor respondeu',
            self::Updated => 'Reclamação atualizada',
            self::ResolutionProposed => 'A empresa propôs uma solução',
            self::Resolved => 'Problema resolvido',
            self::MarkedUnresolved => 'Marcada como não resolvida',
            self::Rated => 'Avaliação do consumidor',
            self::Closed => 'Reclamação encerrada',
            self::Reopened => 'Reclamação reaberta',
            self::Reported => 'Conteúdo denunciado',
            self::Removed => 'Reclamação removida',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Approved, self::Published, self::Resolved => 'check',
            self::Rejected, self::Removed, self::MarkedUnresolved => 'x',
            self::CompanyReplied, self::ConsumerReplied, self::ResolutionProposed => 'chat',
            self::Rated => 'star',
            default => 'dot',
        };
    }

    public function isPublicByDefault(): bool
    {
        return in_array($this, [
            self::Published,
            self::CompanyNotified,
            self::CompanyReplied,
            self::ConsumerReplied,
            self::Updated,
            self::ResolutionProposed,
            self::Resolved,
            self::MarkedUnresolved,
            self::Rated,
            self::Closed,
            self::Reopened,
        ], true);
    }
}
