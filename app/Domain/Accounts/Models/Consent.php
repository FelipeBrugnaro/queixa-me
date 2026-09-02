<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Models;

use App\Domain\Accounts\Enums\ConsentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registo imutavel de consentimento. Nunca deve ser actualizado: revogar
 * cria um novo registo com granted = false, preservando o historico.
 */
class Consent extends Model
{
    protected $fillable = [
        'user_id', 'type', 'document_version', 'granted',
        'subject_type', 'subject_id', 'granted_at', 'revoked_at',
        'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'type' => ConsentType::class,
            'granted' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isCurrent(): bool
    {
        return $this->granted
            && $this->revoked_at === null
            && $this->document_version === $this->type->currentVersion();
    }
}
