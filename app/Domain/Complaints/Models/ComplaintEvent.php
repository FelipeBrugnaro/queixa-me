<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Models;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Enums\ActorType;
use App\Domain\Complaints\Enums\ComplaintEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Evento imutavel da timeline de uma reclamacao. */
class ComplaintEvent extends Model
{
    protected $fillable = [
        'complaint_id', 'type', 'actor_type', 'actor_user_id', 'actor_company_id',
        'from_status', 'to_status', 'summary', 'payload', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'type' => ComplaintEventType::class,
            'actor_type' => ActorType::class,
            'payload' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'actor_company_id');
    }

    public function actorLabel(): string
    {
        return match ($this->actor_type) {
            ActorType::Company => $this->actorCompany?->name ?? 'Empresa',
            ActorType::Consumer => $this->complaint?->authorDisplayName() ?? 'Consumidor',
            ActorType::Moderator => 'Equipa queixa.me',
            ActorType::System => 'queixa.me',
        };
    }
}
