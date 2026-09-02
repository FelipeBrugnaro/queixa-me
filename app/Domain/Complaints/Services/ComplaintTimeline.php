<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Services;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Enums\ActorType;
use App\Domain\Complaints\Enums\ComplaintEventType;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Models\ComplaintEvent;

/**
 * Escritor unico da timeline. Todas as mudancas de estado passam por aqui
 * para que o historico seja completo por construcao, e nao por disciplina
 * de quem escreve o codigo.
 */
class ComplaintTimeline
{
    public function record(
        Complaint $complaint,
        ComplaintEventType $type,
        ActorType $actorType = ActorType::System,
        ?User $actorUser = null,
        ?Company $actorCompany = null,
        ?string $summary = null,
        array $payload = [],
        ?bool $isPublic = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
    ): ComplaintEvent {
        $event = $complaint->events()->create([
            'type' => $type,
            'actor_type' => $actorType,
            'actor_user_id' => $actorUser?->id,
            'actor_company_id' => $actorCompany?->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'summary' => $summary,
            'payload' => $payload ?: null,
            'is_public' => $isPublic ?? $type->isPublicByDefault(),
        ]);

        $complaint->forceFill(['last_activity_at' => now()])->saveQuietly();

        return $event;
    }
}
