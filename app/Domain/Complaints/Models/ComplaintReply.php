<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Models;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Enums\ActorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ComplaintReply extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'complaint_id', 'parent_id', 'author_type', 'user_id', 'company_id',
        'author_display_name', 'body', 'is_resolution_proposal',
        'moderation_status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'author_type' => ActorType::class,
            'is_resolution_proposal' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $reply): void {
            $reply->uuid ??= (string) Str::uuid();
        });
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isFromCompany(): bool
    {
        return $this->author_type === ActorType::Company;
    }

    public function displayName(): string
    {
        if ($this->author_display_name) {
            return $this->author_display_name;
        }

        return $this->isFromCompany()
            ? ($this->company?->name ?? 'Empresa')
            : ($this->complaint?->authorDisplayName() ?? 'Consumidor');
    }
}
