<?php

declare(strict_types=1);

namespace App\Domain\Ratings\Models;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyCategory;
use App\Domain\Ratings\Enums\AwardType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandAward extends Model
{
    protected $fillable = [
        'company_id', 'category_id', 'award_type', 'period_start', 'position',
        'metric_value', 'editorial_note', 'is_editorial', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'award_type' => AwardType::class,
            'period_start' => 'date',
            'metric_value' => 'float',
            'is_editorial' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompanyCategory::class, 'category_id');
    }
}
