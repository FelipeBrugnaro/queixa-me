<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Services;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Enums\ActorType;
use App\Domain\Complaints\Enums\ComplaintEventType;
use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Enums\ModerationStatus;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Models\ComplaintReply;
use App\Domain\Moderation\Enums\RejectionReason;
use App\Domain\Moderation\Models\ModerationReview;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Maquina de estados da reclamacao.
 *
 * Toda a regra de transicao vive aqui: os controllers apenas pedem accoes.
 * Cada transicao valida a origem, actualiza os dois eixos de estado
 * (moderacao e ciclo de vida), escreve a timeline e agenda o recalculo dos
 * indicadores da empresa.
 */
class ComplaintWorkflow
{
    public function __construct(
        private readonly ComplaintTimeline $timeline,
        private readonly SensitiveDataScanner $scanner,
    ) {}

    // -----------------------------------------------------------------
    // Circuito de moderacao
    // -----------------------------------------------------------------

    public function submit(Complaint $complaint, User $author): Complaint
    {
        if (! in_array($complaint->moderation_status, [ModerationStatus::Draft, ModerationStatus::ChangesRequested], true)) {
            throw new RuntimeException('Esta reclamação já foi submetida.');
        }

        $isResubmission = $complaint->moderation_status === ModerationStatus::ChangesRequested;
        $findings = $this->scanner->scan($complaint->title, $complaint->description, (string) $complaint->desired_resolution);

        DB::transaction(function () use ($complaint, $author, $findings, $isResubmission): void {
            $complaint->forceFill([
                'moderation_status' => ModerationStatus::Submitted,
                'submitted_at' => $complaint->submitted_at ?? now(),
                'sensitive_flags' => $findings ?: null,
                'priority' => $this->scanner->priorityBoost($findings),
            ])->save();

            $this->timeline->record(
                $complaint,
                $isResubmission ? ComplaintEventType::Resubmitted : ComplaintEventType::Submitted,
                ActorType::Consumer,
                actorUser: $author,
                toStatus: ModerationStatus::Submitted->value,
                isPublic: false,
            );
        });

        return $complaint->refresh();
    }

    public function startReview(Complaint $complaint, User $moderator): Complaint
    {
        $this->assertPending($complaint);

        $complaint->forceFill(['moderation_status' => ModerationStatus::InReview])->save();

        $this->timeline->record(
            $complaint,
            ComplaintEventType::ReviewStarted,
            ActorType::Moderator,
            actorUser: $moderator,
            toStatus: ModerationStatus::InReview->value,
            isPublic: false,
        );

        return $complaint;
    }

    public function approve(Complaint $complaint, User $moderator, ?string $notes = null): Complaint
    {
        $this->assertPending($complaint);

        DB::transaction(function () use ($complaint, $moderator, $notes): void {
            $slug = $complaint->slug ?? Complaint::generateSlug(
                $complaint->title,
                $complaint->company?->name ?? (string) $complaint->company_name_raw,
                $complaint->reference,
            );

            $complaint->forceFill([
                'moderation_status' => ModerationStatus::Approved,
                'stage' => ComplaintStage::Published,
                'slug' => $slug,
                'approved_at' => now(),
                'published_at' => $complaint->published_at ?? now(),
            ])->save();

            $this->recordReview($complaint, $moderator, 'approved', notes: $notes);

            $this->timeline->record(
                $complaint,
                ComplaintEventType::Approved,
                ActorType::Moderator,
                actorUser: $moderator,
                toStatus: ModerationStatus::Approved->value,
                isPublic: false,
            );

            $this->timeline->record(
                $complaint,
                ComplaintEventType::Published,
                ActorType::System,
                summary: 'A reclamação foi publicada no portal.',
            );
        });

        return $complaint->refresh();
    }

    public function requestChanges(
        Complaint $complaint,
        User $moderator,
        RejectionReason $reason,
        ?string $message = null,
    ): Complaint {
        $this->assertPending($complaint);

        DB::transaction(function () use ($complaint, $moderator, $reason, $message): void {
            $complaint->forceFill(['moderation_status' => ModerationStatus::ChangesRequested])->save();

            $this->recordReview($complaint, $moderator, 'changes_requested', $reason, $message);

            $this->timeline->record(
                $complaint,
                ComplaintEventType::ChangesRequested,
                ActorType::Moderator,
                actorUser: $moderator,
                summary: $message ?? $reason->guidanceForAuthor(),
                payload: ['reason' => $reason->value],
                isPublic: false,
                toStatus: ModerationStatus::ChangesRequested->value,
            );
        });

        return $complaint->refresh();
    }

    public function reject(
        Complaint $complaint,
        User $moderator,
        RejectionReason $reason,
        ?string $message = null,
    ): Complaint {
        DB::transaction(function () use ($complaint, $moderator, $reason, $message): void {
            $complaint->forceFill([
                'moderation_status' => ModerationStatus::Rejected,
                'stage' => ComplaintStage::NotPublished,
                'rejected_at' => now(),
            ])->save();

            $this->recordReview($complaint, $moderator, 'rejected', $reason, $message);

            $this->timeline->record(
                $complaint,
                ComplaintEventType::Rejected,
                ActorType::Moderator,
                actorUser: $moderator,
                summary: $message ?? $reason->label(),
                payload: ['reason' => $reason->value],
                isPublic: false,
                toStatus: ModerationStatus::Rejected->value,
            );
        });

        return $complaint->refresh();
    }

    /** Remocao de conteudo ja publicado (denuncia procedente, decisao legal). */
    public function remove(Complaint $complaint, User $moderator, string $reason): Complaint
    {
        DB::transaction(function () use ($complaint, $moderator, $reason): void {
            $complaint->forceFill([
                'moderation_status' => ModerationStatus::Removed,
                'is_indexable' => false,
            ])->save();

            $this->recordReview($complaint, $moderator, 'removed', notes: $reason);

            $this->timeline->record(
                $complaint,
                ComplaintEventType::Removed,
                ActorType::Moderator,
                actorUser: $moderator,
                summary: $reason,
                isPublic: false,
            );
        });

        return $complaint->refresh();
    }

    // -----------------------------------------------------------------
    // Ciclo de vida publico
    // -----------------------------------------------------------------

    public function markCompanyNotified(Complaint $complaint): Complaint
    {
        if ($complaint->company_notified_at !== null || ! $complaint->isPublished()) {
            return $complaint;
        }

        $complaint->forceFill([
            'company_notified_at' => now(),
            'stage' => ComplaintStage::CompanyNotified,
        ])->save();

        $this->timeline->record(
            $complaint,
            ComplaintEventType::CompanyNotified,
            ActorType::System,
            summary: 'A empresa foi notificada desta reclamação.',
        );

        return $complaint;
    }

    public function addCompanyReply(
        Complaint $complaint,
        Company $company,
        User $author,
        string $body,
        bool $isResolutionProposal = false,
        ?string $displayName = null,
    ): ComplaintReply {
        if (! $complaint->canBeRepliedByCompany()) {
            throw new RuntimeException('Esta reclamação não aceita novas respostas.');
        }

        return DB::transaction(function () use ($complaint, $company, $author, $body, $isResolutionProposal, $displayName) {
            $reply = $complaint->replies()->create([
                'author_type' => ActorType::Company,
                'user_id' => $author->id,
                'company_id' => $company->id,
                'author_display_name' => $displayName ?? $company->name,
                'body' => $body,
                'is_resolution_proposal' => $isResolutionProposal,
                'moderation_status' => ModerationStatus::Approved->value,
                'published_at' => now(),
            ]);

            $isFirstResponse = $complaint->first_response_at === null;

            $complaint->forceFill([
                'first_response_at' => $complaint->first_response_at ?? now(),
                'resolution_proposed_at' => $isResolutionProposal
                    ? ($complaint->resolution_proposed_at ?? now())
                    : $complaint->resolution_proposed_at,
                'stage' => $isFirstResponse ? ComplaintStage::CompanyReplied : ComplaintStage::InFollowUp,
                'replies_count' => $complaint->replies_count + 1,
            ])->save();

            $this->timeline->record(
                $complaint,
                $isResolutionProposal ? ComplaintEventType::ResolutionProposed : ComplaintEventType::CompanyReplied,
                ActorType::Company,
                actorUser: $author,
                actorCompany: $company,
                payload: ['reply_uuid' => $reply->uuid],
            );

            return $reply;
        });
    }

    public function addConsumerReply(Complaint $complaint, User $author, string $body): ComplaintReply
    {
        if ($author->id !== $complaint->user_id) {
            throw new RuntimeException('Apenas o autor pode responder na sua reclamação.');
        }

        return DB::transaction(function () use ($complaint, $author, $body) {
            $reply = $complaint->replies()->create([
                'author_type' => ActorType::Consumer,
                'user_id' => $author->id,
                'author_display_name' => $complaint->authorDisplayName(),
                'body' => $body,
                'moderation_status' => ModerationStatus::Approved->value,
                'published_at' => now(),
            ]);

            $complaint->forceFill([
                'replies_count' => $complaint->replies_count + 1,
                'stage' => $complaint->stage->hasCompanyReply() ? ComplaintStage::InFollowUp : $complaint->stage,
            ])->save();

            $this->timeline->record(
                $complaint,
                ComplaintEventType::ConsumerReplied,
                ActorType::Consumer,
                actorUser: $author,
                payload: ['reply_uuid' => $reply->uuid],
            );

            return $reply;
        });
    }

    /**
     * DECISAO DE PRODUTO: so o consumidor pode declarar um problema resolvido.
     *
     * Se a empresa pudesse fechar as suas proprias reclamacoes, a taxa de
     * resolucao deixaria de medir resolucao e passaria a medir vontade de
     * fechar processos. A empresa propoe; o consumidor confirma ou recusa.
     */
    public function confirmResolution(Complaint $complaint, User $author, ?int $rating = null, ?bool $wouldRecommend = null, ?string $comment = null): Complaint
    {
        if ($author->id !== $complaint->user_id) {
            throw new RuntimeException('Apenas o autor pode confirmar a resolução.');
        }

        DB::transaction(function () use ($complaint, $author, $rating, $wouldRecommend, $comment): void {
            $complaint->forceFill([
                'stage' => ComplaintStage::Resolved,
                'resolved_at' => now(),
                'rating' => $rating ?? $complaint->rating,
                'would_recommend' => $wouldRecommend ?? $complaint->would_recommend,
                'rating_comment' => $comment ?? $complaint->rating_comment,
                'rated_at' => $rating !== null ? now() : $complaint->rated_at,
            ])->save();

            $this->timeline->record(
                $complaint,
                ComplaintEventType::Resolved,
                ActorType::Consumer,
                actorUser: $author,
                summary: 'O consumidor confirmou que o problema ficou resolvido.',
            );

            if ($rating !== null) {
                $this->timeline->record(
                    $complaint,
                    ComplaintEventType::Rated,
                    ActorType::Consumer,
                    actorUser: $author,
                    payload: ['rating' => $rating, 'would_recommend' => $wouldRecommend],
                );
            }
        });

        return $complaint->refresh();
    }

    public function markUnresolved(Complaint $complaint, User $author, ?int $rating = null, ?string $comment = null): Complaint
    {
        if ($author->id !== $complaint->user_id) {
            throw new RuntimeException('Apenas o autor pode marcar como não resolvida.');
        }

        DB::transaction(function () use ($complaint, $author, $rating, $comment): void {
            $complaint->forceFill([
                'stage' => ComplaintStage::Unresolved,
                'rating' => $rating ?? $complaint->rating,
                'rating_comment' => $comment ?? $complaint->rating_comment,
                'rated_at' => $rating !== null ? now() : $complaint->rated_at,
            ])->save();

            $this->timeline->record(
                $complaint,
                ComplaintEventType::MarkedUnresolved,
                ActorType::Consumer,
                actorUser: $author,
                summary: 'O consumidor indicou que o problema não ficou resolvido.',
            );
        });

        return $complaint->refresh();
    }

    public function close(Complaint $complaint, string $summary = 'Reclamação encerrada por inatividade.'): Complaint
    {
        $complaint->forceFill([
            'stage' => ComplaintStage::Closed,
            'closed_at' => now(),
        ])->save();

        $this->timeline->record($complaint, ComplaintEventType::Closed, ActorType::System, summary: $summary);

        return $complaint;
    }

    public function reopen(Complaint $complaint, User $author, string $reason): Complaint
    {
        $complaint->forceFill([
            'stage' => ComplaintStage::InFollowUp,
            'closed_at' => null,
            'resolved_at' => null,
        ])->save();

        $this->timeline->record(
            $complaint,
            ComplaintEventType::Reopened,
            ActorType::Consumer,
            actorUser: $author,
            summary: $reason,
        );

        return $complaint;
    }

    // -----------------------------------------------------------------
    // Auxiliares
    // -----------------------------------------------------------------

    private function assertPending(Complaint $complaint): void
    {
        if (! $complaint->moderation_status->isPending()) {
            throw new RuntimeException('Esta reclamação não está na fila de moderação.');
        }
    }

    private function recordReview(
        Complaint $complaint,
        User $moderator,
        string $action,
        ?RejectionReason $reason = null,
        ?string $message = null,
        ?string $notes = null,
    ): ModerationReview {
        return $complaint->moderationReviews()->create([
            'moderator_id' => $moderator->id,
            'action' => $action,
            'reason_code' => $reason?->value,
            'message_to_author' => $message,
            'notes' => $notes,
            'flags' => $complaint->sensitive_flags,
        ]);
    }
}
