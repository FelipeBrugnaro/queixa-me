<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Services;

use App\Domain\Accounts\Enums\ConsentType;
use App\Domain\Accounts\Models\Consent;
use App\Domain\Accounts\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Registo probatorio de consentimentos.
 *
 * O RGPD (art. 7 n.1) exige que o responsavel consiga DEMONSTRAR que o
 * titular consentiu. Guardar um booleano "aceitou_termos" na tabela de
 * utilizadores nao demonstra nada: nao diz que texto foi aceite, quando,
 * nem a partir de onde. Cada aceitacao gera aqui uma linha imutavel com
 * tipo, versao do documento, data/hora, IP e user agent.
 */
class ConsentRecorder
{
    public function __construct(private readonly Request $request) {}

    public function record(
        ConsentType $type,
        ?User $user = null,
        ?Model $subject = null,
        bool $granted = true,
        ?string $version = null,
    ): Consent {
        return Consent::create([
            'user_id' => $user?->id,
            'type' => $type,
            'document_version' => $version ?? $type->currentVersion(),
            'granted' => $granted,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'granted_at' => now(),
            'revoked_at' => $granted ? null : now(),
            'ip_address' => $this->request->ip(),
            'user_agent' => substr((string) $this->request->userAgent(), 0, 500),
        ]);
    }

    /**
     * @param  array<int,ConsentType>  $types
     * @return array<int,Consent>
     */
    public function recordMany(array $types, ?User $user = null, ?Model $subject = null): array
    {
        return array_map(fn (ConsentType $type) => $this->record($type, $user, $subject), $types);
    }

    /** Revogar cria um novo registo: o historico nunca e reescrito. */
    public function revoke(ConsentType $type, User $user): Consent
    {
        return $this->record($type, $user, granted: false);
    }

    public function hasCurrentConsent(User $user, ConsentType $type): bool
    {
        $latest = Consent::where('user_id', $user->id)
            ->where('type', $type->value)
            ->latest('id')
            ->first();

        return $latest?->isCurrent() ?? false;
    }

    /**
     * Tipos cuja versao aceite ficou desatualizada e que exigem reconsentimento.
     *
     * @return array<int,ConsentType>
     */
    public function outdatedConsents(User $user): array
    {
        $required = [ConsentType::Terms, ConsentType::Privacy, ConsentType::DataProtection];

        return array_values(array_filter(
            $required,
            fn (ConsentType $type) => ! $this->hasCurrentConsent($user, $type)
        ));
    }
}
