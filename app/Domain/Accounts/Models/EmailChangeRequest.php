<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailChangeRequest extends Model
{
    protected $fillable = [
        'user_id', 'new_email', 'token_hash', 'expires_at',
        'confirmed_at', 'cancelled_at', 'requested_ip',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->confirmed_at === null
            && $this->cancelled_at === null
            && $this->expires_at->isFuture();
    }
}
