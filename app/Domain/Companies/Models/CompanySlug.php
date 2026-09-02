<?php

declare(strict_types=1);

namespace App\Domain\Companies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historico de slugs de uma empresa, usado para redirecionar 301. */
class CompanySlug extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['company_id', 'slug', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
