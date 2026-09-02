<?php

declare(strict_types=1);

namespace App\Domain\Ratings\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformStat extends Model
{
    protected $fillable = [
        'date', 'complaints_count', 'published_count', 'replied_count',
        'resolved_count', 'companies_count', 'users_count',
        'avg_response_rate', 'avg_resolution_rate', 'avg_rating',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'avg_response_rate' => 'float',
            'avg_resolution_rate' => 'float',
            'avg_rating' => 'float',
        ];
    }
}
