<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Models;

use App\Domain\Accounts\Enums\Gender;
use App\Domain\Accounts\Enums\UserStatus;
use App\Domain\Accounts\Enums\UserType;
use App\Domain\Companies\Enums\CompanyRole;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Messaging\Models\Conversation;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'uuid', 'type', 'status', 'public_name', 'name', 'first_name', 'last_name',
        'birthdate', 'gender', 'email', 'password', 'phone', 'country',
        'district', 'locality', 'avatar_path', 'locale', 'marketing_opt_in',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'type' => UserType::class,
            'status' => UserStatus::class,
            'gender' => Gender::class,
            'birthdate' => 'date',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'blocked_at' => 'datetime',
            'anonymised_at' => 'datetime',
            'marketing_opt_in' => 'boolean',
            'is_staff' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->uuid ??= (string) Str::uuid();
        });
    }

    // -----------------------------------------------------------------
    // Relacoes
    // -----------------------------------------------------------------

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function emailChangeRequests(): HasMany
    {
        return $this->hasMany(EmailChangeRequest::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_users')
            ->withPivot(['role', 'job_title', 'accepted_at', 'revoked_at'])
            ->wherePivotNull('revoked_at')
            ->withTimestamps();
    }

    public function followedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_follows')->withTimestamps();
    }

    // -----------------------------------------------------------------
    // Identidade
    // -----------------------------------------------------------------

    /**
     * Nome mostrado em contextos publicos. Nunca devolve o nome civil:
     * se nao houver nome publico definido, devolve um pseudonimo estavel.
     */
    public function publicDisplayName(): string
    {
        if ($this->status === UserStatus::Anonymised) {
            return 'Utilizador removido';
        }

        return $this->public_name ?: 'Utilizador '.Str::upper(Str::substr((string) $this->uuid, 0, 6));
    }

    /** Nome civil completo. Uso interno / transmissao a empresa. */
    public function fullName(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : $this->name;
    }

    public function initials(): string
    {
        $source = $this->publicDisplayName();
        $parts = preg_split('/\s+/', trim($source)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= Str::upper(Str::substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'U';
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }

    public function age(): ?int
    {
        return $this->birthdate?->diffInYears(Carbon::now());
    }

    // -----------------------------------------------------------------
    // Papeis e permissoes
    // -----------------------------------------------------------------

    public function isConsumer(): bool
    {
        return $this->type === UserType::Consumer;
    }

    public function isBusiness(): bool
    {
        return $this->type === UserType::Business;
    }

    public function isModerator(): bool
    {
        return in_array($this->type, [UserType::Moderator, UserType::Admin], true);
    }

    public function isAdmin(): bool
    {
        return $this->type === UserType::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /** Empresa atualmente gerida (primeira ativa). */
    public function primaryCompany(): ?Company
    {
        return $this->companies()->first();
    }

    public function roleInCompany(Company $company): ?CompanyRole
    {
        $pivot = $this->companies()->where('companies.id', $company->id)->first()?->pivot;

        return $pivot ? CompanyRole::tryFrom((string) $pivot->role) : null;
    }

    public function canForCompany(Company $company, string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->roleInCompany($company);

        return $role !== null && in_array($permission, $role->permissions(), true);
    }

    // -----------------------------------------------------------------
    // Perfil
    // -----------------------------------------------------------------

    /**
     * Campos do perfil em falta que a reclamacao precisa de recolher.
     *
     * @return array<int,string>
     */
    public function missingComplaintProfileFields(): array
    {
        $required = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'country' => $this->country,
            'district' => $this->district,
            'locality' => $this->locality,
        ];

        return array_keys(array_filter($required, static fn ($value) => blank($value)));
    }

    public function profileCompletion(): int
    {
        $fields = [
            $this->public_name, $this->first_name, $this->last_name,
            $this->birthdate, $this->gender, $this->phone,
            $this->country, $this->district, $this->locality, $this->avatar_path,
        ];

        $filled = count(array_filter($fields, static fn ($value) => filled($value)));

        return (int) round($filled / count($fields) * 100);
    }

    public function unreadMessagesCount(): int
    {
        return (int) $this->conversations()->sum('user_unread_count');
    }
}
