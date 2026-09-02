<?php

declare(strict_types=1);

namespace App\Domain\Seo\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = ['from_path', 'to_path', 'status_code', 'hits', 'last_hit_at'];

    protected function casts(): array
    {
        return ['last_hit_at' => 'datetime'];
    }
}
