<?php

declare(strict_types=1);

namespace App\Domain\Companies\Models;

use App\Domain\Accounts\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyClaim extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'status', 'work_email', 'vat_number',
        'evidence', 'decision_notes', 'reviewed_by_user_id', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
