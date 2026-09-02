<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Models;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Enums\ActorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id', 'sender_type', 'sender_user_id', 'sender_company_id',
        'sender_display_name', 'body', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'sender_type' => ActorType::class,
            'read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $message): void {
            $message->uuid ??= (string) Str::uuid();
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function senderCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'sender_company_id');
    }

    public function isFromCompany(): bool
    {
        return $this->sender_type === ActorType::Company;
    }

    public function displayName(): string
    {
        return $this->sender_display_name
            ?: ($this->isFromCompany() ? ($this->senderCompany?->name ?? 'Empresa') : 'Consumidor');
    }
}
