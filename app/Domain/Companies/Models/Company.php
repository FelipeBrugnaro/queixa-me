<?php

declare(strict_types=1);

namespace App\Domain\Companies\Models;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Enums\CompanyStatus;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Ratings\Models\BrandAward;
use App\Domain\Ratings\Models\CompanyStat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'legal_name', 'slug', 'category_id', 'status', 'description',
        'website', 'support_email', 'support_phone', 'vat_number', 'logo_path',
        'cover_path', 'brand_color', 'country', 'district', 'locality',
        'address', 'postal_code', 'meta_title', 'meta_description',
        'accepts_complaints', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'claimed_at' => 'datetime',
            'verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'is_indexable' => 'boolean',
            'accepts_complaints' => 'boolean',
            'satisfaction_index' => 'float',
            'response_rate' => 'float',
            'resolution_rate' => 'float',
            'average_rating' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $company): void {
            $company->uuid ??= (string) Str::uuid();
            $company->slug ??= static::generateSlug($company->name);
        });

        static::created(function (self $company): void {
            $company->slugs()->create(['slug' => $company->slug, 'created_at' => now()]);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // -----------------------------------------------------------------
    // Relacoes
    // -----------------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompanyCategory::class, 'category_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function slugs(): HasMany
    {
        return $this->hasMany(CompanySlug::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_users')
            ->withPivot(['role', 'job_title', 'accepted_at', 'revoked_at'])
            ->wherePivotNull('revoked_at')
            ->withTimestamps();
    }

    public function claims(): HasMany
    {
        return $this->hasMany(CompanyClaim::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(CompanyStat::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(BrandAward::class);
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopePublic(Builder $query): Builder
    {
        return $query->whereIn('status', [CompanyStatus::Active->value, CompanyStatus::Verified->value]);
    }

    public function scopeRankable(Builder $query): Builder
    {
        return $query->public()
            ->whereNotNull('satisfaction_index')
            ->where('published_complaints_count', '>=', config('queixame.index.ranking_minimum_complaints'));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('name', 'like', $term.'%')
                ->orWhere('name', 'like', '%'.$term.'%')
                ->orWhere('legal_name', 'like', '%'.$term.'%');
        });
    }

    // -----------------------------------------------------------------
    // Comportamento
    // -----------------------------------------------------------------

    public static function generateSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'empresa';
        $slug = $base;
        $suffix = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists()
            || CompanySlug::where('slug', $slug)->when($ignoreId, fn (Builder $q) => $q->where('company_id', '!=', $ignoreId))->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public function isPublic(): bool
    {
        return $this->status->isPubliclyVisible();
    }

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= Str::upper(Str::substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'E';
    }

    public function url(): string
    {
        return route('companies.show', $this->slug);
    }

    /**
     * Paginas de empresa sem reclamacoes publicadas sao thin content.
     * Mante-las fora do indice protege a qualidade global do dominio quando
     * o portal escalar para centenas de milhares de fichas.
     */
    public function shouldBeIndexed(): bool
    {
        return $this->isPublic()
            && $this->published_complaints_count >= (int) config('queixame.seo.company_min_complaints_to_index');
    }

    public function satisfactionLabel(): string
    {
        return match (true) {
            $this->satisfaction_index === null => 'Sem dados suficientes',
            $this->satisfaction_index >= 80 => 'Muito bom',
            $this->satisfaction_index >= 65 => 'Bom',
            $this->satisfaction_index >= 50 => 'Razoável',
            $this->satisfaction_index >= 35 => 'Fraco',
            default => 'Muito fraco',
        };
    }

    /**
     * Escala de desfecho, em cinco degraus.
     *
     * Vai do terracota (ignora quem reclama) ao verde da marca (resolve) —
     * a mesma escala em toda a plataforma, para que a cor de um índice
     * signifique sempre o mesmo, esteja onde estiver.
     */
    public function satisfactionColorClasses(): string
    {
        return match (true) {
            $this->satisfaction_index === null => 'bg-ink-100 text-ink-600 ring-ink-200',
            $this->satisfaction_index >= 80 => 'bg-brand-50 text-brand-800 ring-brand-200',
            $this->satisfaction_index >= 65 => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            $this->satisfaction_index >= 50 => 'bg-amber-50 text-amber-800 ring-amber-200',
            $this->satisfaction_index >= 35 => 'bg-amber-100 text-amber-900 ring-amber-300',
            default => 'bg-rose-50 text-rose-700 ring-rose-200',
        };
    }

    /** Cor sólida da barra de escala do índice. */
    public function satisfactionBarClass(): string
    {
        return match (true) {
            $this->satisfaction_index === null => 'bg-ink-300',
            $this->satisfaction_index >= 80 => 'bg-brand-600',
            $this->satisfaction_index >= 65 => 'bg-emerald-500',
            $this->satisfaction_index >= 50 => 'bg-amber-400',
            $this->satisfaction_index >= 35 => 'bg-amber-500',
            default => 'bg-rose-500',
        };
    }

    public function hasEnoughDataForIndex(): bool
    {
        return $this->published_complaints_count >= (int) config('queixame.index.ranking_minimum_complaints');
    }
}
