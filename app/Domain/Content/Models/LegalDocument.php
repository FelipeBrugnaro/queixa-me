<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Documento legal versionado. Os registos de consentimento apontam para a
 * versao vigente no momento da aceitacao, o que permite provar exatamente
 * que texto o utilizador aceitou.
 */
class LegalDocument extends Model
{
    protected $fillable = [
        'key', 'title', 'slug', 'version', 'body',
        'meta_description', 'effective_from', 'is_current',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    public static function current(string $key): ?self
    {
        return static::where('key', $key)->where('is_current', true)->first();
    }
}
