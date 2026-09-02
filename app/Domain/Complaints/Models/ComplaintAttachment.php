<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Models;

use App\Domain\Accounts\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ComplaintAttachment extends Model
{
    protected $fillable = [
        'complaint_id', 'reply_id', 'uploaded_by_user_id', 'disk', 'path',
        'original_name', 'mime_type', 'size_bytes', 'checksum', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'size_bytes' => 'integer',
            'scanned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $attachment): void {
            $attachment->uuid ??= (string) Str::uuid();
        });
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function humanSize(): string
    {
        $bytes = $this->size_bytes;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        return max(1, (int) round($bytes / 1024)).' KB';
    }

    /** Os anexos nunca sao servidos diretamente do disco. */
    public function downloadUrl(): string
    {
        return route('attachments.show', $this->uuid);
    }
}
