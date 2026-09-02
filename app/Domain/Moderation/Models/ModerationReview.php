<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Models;

use App\Domain\Accounts\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationReview extends Model
{
    protected $fillable = [
        'reviewable_type', 'reviewable_id', 'moderator_id', 'action',
        'reason_code', 'notes', 'message_to_author', 'flags', 'review_seconds',
    ];

    protected function casts(): array
    {
        return ['flags' => 'array'];
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
