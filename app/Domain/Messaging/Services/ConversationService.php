<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Services;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Enums\ActorType;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Canal privado empresa ↔ consumidor.
 *
 * Existe para resolver uma tensão real: a empresa precisa de dados que não
 * devem ser públicos (número de encomenda, morada, IBAN para reembolso) e o
 * consumidor não deve ter de os publicar para ser atendido.
 *
 * Regras: só a empresa visada numa reclamação pode abrir conversa, e o
 * consumidor pode fechá-la a qualquer momento. Sem isto, o canal tornar-se-ia
 * uma via de pressão sobre quem reclama.
 */
class ConversationService
{
    public function openFromComplaint(Complaint $complaint, Company $company, User $agent, string $body): Conversation
    {
        if ($complaint->company_id !== $company->id) {
            throw new RuntimeException('Esta reclamação não pertence à tua empresa.');
        }

        if (! $complaint->isPublished()) {
            throw new RuntimeException('Só é possível contactar o consumidor depois da publicação.');
        }

        return DB::transaction(function () use ($complaint, $company, $agent, $body): Conversation {
            $conversation = Conversation::firstOrCreate(
                ['complaint_id' => $complaint->id, 'company_id' => $company->id, 'user_id' => $complaint->user_id],
                ['subject' => $complaint->title],
            );

            $this->send($conversation, ActorType::Company, $body, $agent, $company);

            return $conversation;
        });
    }

    public function send(
        Conversation $conversation,
        ActorType $senderType,
        string $body,
        ?User $user = null,
        ?Company $company = null,
    ): Message {
        if ($conversation->isClosed()) {
            throw new RuntimeException('Esta conversa está encerrada.');
        }

        return DB::transaction(function () use ($conversation, $senderType, $body, $user, $company): Message {
            $message = $conversation->messages()->create([
                'sender_type' => $senderType,
                'sender_user_id' => $user?->id,
                'sender_company_id' => $company?->id,
                'sender_display_name' => $senderType === ActorType::Company
                    ? ($company?->name ?? 'Empresa')
                    : ($conversation->user?->publicDisplayName() ?? 'Consumidor'),
                'body' => $body,
            ]);

            // Incrementos atómicos em vez de ler-somar-gravar: dois agentes da
            // mesma empresa a responder ao mesmo tempo perderiam contagens,
            // e uma conversa acabada de criar nem sequer tem estes atributos
            // carregados em memória.
            $counters = ['messages_count' => 1];

            if ($senderType === ActorType::Company) {
                $counters['user_unread_count'] = 1;
            } else {
                $counters['company_unread_count'] = 1;
            }

            $conversation->newQuery()
                ->whereKey($conversation->getKey())
                ->incrementEach($counters, ['last_message_at' => now()]);

            $conversation->refresh();

            return $message;
        });
    }

    public function markRead(Conversation $conversation, ActorType $reader): void
    {
        $conversation->forceFill(
            $reader === ActorType::Company
                ? ['company_unread_count' => 0]
                : ['user_unread_count' => 0]
        )->save();

        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_type', '!=', $reader->value)
            ->update(['read_at' => now()]);
    }

    public function markUnread(Conversation $conversation, ActorType $reader): void
    {
        $conversation->forceFill(
            $reader === ActorType::Company
                ? ['company_unread_count' => 1]
                : ['user_unread_count' => 1]
        )->save();
    }

    public function closeByUser(Conversation $conversation): void
    {
        $conversation->forceFill(['closed_by_user_at' => now()])->save();
    }
}
