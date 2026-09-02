<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dados de contacto do reclamante transmitidos a entidade visada.
 *
 * Vivem separados da reclamacao e cifrados em repouso porque tem um ciclo de
 * vida proprio: podem ser expurgados quando deixam de ser necessarios sem
 * afetar o registo publico, e nunca sao carregados nas consultas publicas.
 */
class ComplaintContactDetail extends Model
{
    protected $fillable = [
        'complaint_id', 'first_name', 'last_name', 'email', 'phone',
        'address', 'postal_code', 'locality', 'district', 'country',
        'document_number', 'shared_with_company_at', 'purge_after',
    ];

    protected function casts(): array
    {
        return [
            'first_name' => 'encrypted',
            'last_name' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'address' => 'encrypted',
            'postal_code' => 'encrypted',
            'locality' => 'encrypted',
            'district' => 'encrypted',
            'document_number' => 'encrypted',
            'shared_with_company_at' => 'datetime',
            'purge_after' => 'datetime',
            'purged_at' => 'datetime',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function isPurged(): bool
    {
        return $this->purged_at !== null;
    }

    public function fullName(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    /** Substitui os dados por null mantendo a linha como prova de expurgo. */
    public function purge(): void
    {
        $this->forceFill([
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'phone' => null,
            'address' => null,
            'postal_code' => null,
            'locality' => null,
            'district' => null,
            'document_number' => null,
            'purged_at' => now(),
        ])->save();
    }
}
