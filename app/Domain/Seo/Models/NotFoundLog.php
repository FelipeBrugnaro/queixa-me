<?php

declare(strict_types=1);

namespace App\Domain\Seo\Models;

use Illuminate\Database\Eloquent\Model;

class NotFoundLog extends Model
{
    protected $table = 'not_found_logs';

    protected $fillable = ['path', 'hits', 'last_referer', 'last_hit_at'];

    protected function casts(): array
    {
        return ['last_hit_at' => 'datetime'];
    }
}
