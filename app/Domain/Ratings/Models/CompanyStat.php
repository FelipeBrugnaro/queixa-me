<?php

declare(strict_types=1);

namespace App\Domain\Ratings\Models;

use App\Domain\Companies\Models\Company;
use App\Domain\Ratings\Enums\StatsPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyStat extends Model
{
    protected $fillable = [
        'company_id', 'period_type', 'period_start', 'period_end',
        'complaints_count', 'answerable_count', 'replied_count', 'resolved_count',
        'unresolved_count', 'rated_count', 'response_rate', 'resolution_rate',
        'average_rating', 'would_recommend_rate', 'avg_first_response_minutes',
        'median_first_response_minutes', 'speed_score', 'satisfaction_index',
        'raw_index', 'is_ranked', 'rank_overall', 'rank_in_category',
        'previous_index', 'index_delta', 'breakdown', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_type' => StatsPeriod::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'is_ranked' => 'boolean',
            'breakdown' => 'array',
            'computed_at' => 'datetime',
            'response_rate' => 'float',
            'resolution_rate' => 'float',
            'average_rating' => 'float',
            'would_recommend_rate' => 'float',
            'speed_score' => 'float',
            'satisfaction_index' => 'float',
            'raw_index' => 'float',
            'previous_index' => 'float',
            'index_delta' => 'float',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function trendDirection(): string
    {
        return match (true) {
            $this->index_delta === null => 'flat',
            $this->index_delta > 1.5 => 'up',
            $this->index_delta < -1.5 => 'down',
            default => 'flat',
        };
    }
}
