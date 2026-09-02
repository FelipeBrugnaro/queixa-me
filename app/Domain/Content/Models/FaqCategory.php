<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FaqCategory extends Model
{
    protected $fillable = ['name', 'slug', 'position'];

    protected static function booted(): void
    {
        static::creating(fn (self $category) => $category->slug ??= Str::slug($category->name));
    }

    public function items(): HasMany
    {
        return $this->hasMany(FaqItem::class, 'category_id')->orderBy('position');
    }
}
