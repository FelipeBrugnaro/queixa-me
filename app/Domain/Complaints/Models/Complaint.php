<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Models;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyCategory;
use App\Domain\Complaints\Enums\ComplaintKind;
use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Enums\ModerationStatus;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Moderation\Models\ModerationReview;
use App\Domain\Moderation\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'company_id', 'company_name_raw', 'company_website_raw',
        'category_id', 'kind', 'title', 'description', 'occurred_on',
        'desired_resolution', 'extra_info', 'purchase_reference',
        'amount_involved', 'currency', 'is_identity_public',
        'share_contact_with_company', 'country', 'district', 'locality',
        'submitted_ip',
    ];

    /**
     * Valores iniciais garantidos em memória.
     *
     * A base de dados tem os mesmos defaults, mas esses só se aplicam do lado
     * do servidor: um modelo acabado de criar ficaria sem `moderation_status`
     * carregado e qualquer regra que o lesse veria null. Declará-los aqui faz
     * com que a instância seja coerente desde o primeiro momento.
     */
    protected $attributes = [
        'moderation_status' => 'draft',
        'stage' => 'not_published',
        'kind' => 'consumer',
        'is_identity_public' => true,
        'share_contact_with_company' => false,
        'is_indexable' => true,
        'priority' => 0,
        'views_count' => 0,
        'replies_count' => 0,
        'reports_count' => 0,
        'helpful_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'kind' => ComplaintKind::class,
            'moderation_status' => ModerationStatus::class,
            'stage' => ComplaintStage::class,
            'occurred_on' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'company_notified_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolution_proposed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'rated_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'is_identity_public' => 'boolean',
            'share_contact_with_company' => 'boolean',
            'is_indexable' => 'boolean',
            'would_recommend' => 'boolean',
            'sensitive_flags' => 'array',
            'amount_involved' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $complaint): void {
            $complaint->uuid ??= (string) Str::uuid();
            $complaint->reference ??= static::generateReference();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // -----------------------------------------------------------------
    // Relacoes
    // -----------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompanyCategory::class, 'category_id');
    }

    public function contactDetails(): HasOne
    {
        return $this->hasOne(ComplaintContactDetail::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class);
    }

    public function publicAttachments(): HasMany
    {
        return $this->attachments()->where('is_public', true);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ComplaintEvent::class)->orderBy('created_at');
    }

    public function publicEvents(): HasMany
    {
        return $this->events()->where('is_public', true);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ComplaintReply::class)->orderBy('created_at');
    }

    public function publicReplies(): HasMany
    {
        return $this->replies()->whereNotNull('published_at');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function moderationReviews(): MorphMany
    {
        return $this->morphMany(ModerationReview::class, 'reviewable');
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /** Reclamacoes visiveis ao publico. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('moderation_status', ModerationStatus::Approved->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopePendingModeration(Builder $query): Builder
    {
        return $query->whereIn('moderation_status', [
            ModerationStatus::Submitted->value,
            ModerationStatus::InReview->value,
        ]);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('title', 'like', '%'.$term.'%')
                ->orWhere('description', 'like', '%'.$term.'%');
        });
    }

    /**
     * Relações necessárias para renderizar um cartão de reclamação.
     *
     * Está centralizado num scope porque o cartão é usado em oito páginas
     * diferentes: replicar a lista de `with()` em cada controller garantia
     * que, mais cedo ou mais tarde, uma delas se esquecia de uma relação e
     * disparava um N+1 numa listagem paginada.
     */
    public function scopeForCards(Builder $query): Builder
    {
        return $query->with([
            'company:id,name,slug,logo_path,status',
            'category:id,name,slug',
            'user:id,uuid,public_name,status',
        ]);
    }

    // -----------------------------------------------------------------
    // Identidade e referencias
    // -----------------------------------------------------------------

    public static function generateReference(): string
    {
        do {
            $reference = 'QM-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * O slug e gerado apenas na publicacao e nunca muda depois.
     * URLs estaveis sao um requisito de SEO: um slug que muda invalida
     * ligacoes externas e obriga a cadeias de redirecionamentos.
     */
    public static function generateSlug(string $title, string $companyName, string $reference): string
    {
        $base = Str::slug(Str::limit($title, 70, '').'-'.$companyName);
        $base = trim($base, '-');
        $base = $base !== '' ? $base : 'reclamacao';

        return $base.'-'.Str::lower(Str::afterLast($reference, '-'));
    }

    public function authorDisplayName(): string
    {
        if (! $this->is_identity_public) {
            return 'Reclamação anónima';
        }

        return $this->user?->publicDisplayName() ?? 'Utilizador removido';
    }

    public function url(): string
    {
        return $this->slug
            ? route('complaints.show', $this->slug)
            : route('consumer.complaints.show', $this->uuid);
    }

    // -----------------------------------------------------------------
    // Estado
    // -----------------------------------------------------------------

    public function isPublished(): bool
    {
        return $this->moderation_status === ModerationStatus::Approved
            && $this->published_at !== null;
    }

    public function isEditableByAuthor(): bool
    {
        return $this->moderation_status->isEditableByAuthor();
    }

    public function awaitsCompanyReply(): bool
    {
        return $this->isPublished() && $this->first_response_at === null;
    }

    public function daysWaitingForReply(): ?int
    {
        if (! $this->awaitsCompanyReply() || $this->published_at === null) {
            return null;
        }

        return (int) $this->published_at->diffInDays(now());
    }

    /** A janela de resposta contratada com as empresas ja expirou? */
    public function responseSlaBreached(): bool
    {
        return ($this->daysWaitingForReply() ?? 0) > (int) config('queixame.complaints.response_sla_days');
    }

    public function firstResponseMinutes(): ?int
    {
        if ($this->first_response_at === null || $this->published_at === null) {
            return null;
        }

        return (int) $this->published_at->diffInMinutes($this->first_response_at);
    }

    public function canBeRatedBy(?User $user): bool
    {
        return $user !== null
            && $user->id === $this->user_id
            && $this->rated_at === null
            && $this->stage->hasCompanyReply();
    }

    public function canBeRepliedByCompany(): bool
    {
        return $this->isPublished() && ! in_array($this->stage, [ComplaintStage::Closed], true);
    }

    /**
     * Contribui para os indices da empresa? Reclamacoes laborais e
     * reclamacoes sobre empresas ainda por validar ficam de fora.
     */
    public function countsTowardsIndex(): bool
    {
        return $this->isPublished()
            && $this->kind->countsTowardsIndex()
            && $this->company_id !== null;
    }

    public function shouldBeIndexed(): bool
    {
        return $this->isPublished() && $this->is_indexable && $this->company?->isPublic();
    }

    public function excerpt(int $length = 180): string
    {
        return Str::limit(strip_tags($this->description), $length);
    }
}
