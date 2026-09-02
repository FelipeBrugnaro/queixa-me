<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estrutura pronta para confirmacao por SMS. Nesta versao o codigo e gerado
 * e validado, mas o envio fica a cargo de um driver ainda nao integrado
 * (ver App\Domain\Accounts\Services\PhoneVerificationService).
 */
class PhoneVerification extends Model
{
    protected $fillable = ['user_id', 'phone', 'code_hash', 'attempts', 'expires_at', 'verified_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
