<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Models;

use App\Domain\Accounts\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pedido do titular dos dados (RGPD arts. 15 a 21). */
class DataRequest extends Model
{
    protected $fillable = [
        'user_id', 'type', 'status', 'notes', 'export_path',
        'due_at', 'completed_at', 'handled_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
