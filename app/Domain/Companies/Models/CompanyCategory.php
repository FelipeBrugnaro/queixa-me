<?php

declare(strict_types=1);

namespace App\Domain\Companies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CompanyCategory extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'icon',
        'position', 'meta_title', 'meta_description',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $category): void {
            $category->slug ??= Str::slug($category->name);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'category_id');
    }
}
