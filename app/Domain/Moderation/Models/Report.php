<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Models;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Models\Company;
use App\Domain\Moderation\Enums\ReportReason;
use App\Domain\Moderation\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    protected $fillable = [
        'reportable_type', 'reportable_id', 'reporter_id', 'reporter_company_id',
        'reason', 'details', 'status', 'resolution_notes',
        'resolved_by_user_id', 'resolved_at', 'reporter_ip',
    ];

    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reporterCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'reporter_company_id');
    }
}
