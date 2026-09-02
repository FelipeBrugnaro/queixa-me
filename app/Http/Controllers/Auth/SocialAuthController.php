<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Accounts\Enums\ConsentType;
use App\Domain\Accounts\Enums\UserStatus;
use App\Domain\Accounts\Enums\UserType;
use App\Domain\Accounts\Models\SocialAccount;
use App\Domain\Accounts\Models\User;
use App\Domain\Accounts\Services\ConsentRecorder;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Autenticação com Google e Apple.
 *
 * A ligação a contas existentes segue uma regra estrita: só associamos
 * automaticamente quando o fornecedor confirma que o email é verificado.
 * Caso contrário, qualquer pessoa que criasse uma conta no fornecedor com o
 * email de outra pessoa poderia assumir a conta do queixa.me.
 *
 * A integração com o SDK do fornecedor (Laravel Socialite) fica isolada em
 * resolveProviderUser(): é o único ponto a substituir quando as credenciais
 * de produção existirem.
 */
class SocialAuthController extends Controller
{
    public function __construct(private readonly ConsentRecorder $consents) {}

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureProviderIsConfigured($provider);

        if (! class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
            return redirect()->route('login')->with(
                'warning',
                'O início de sessão com '.ucfirst($provider).' ainda não está disponível.'
            );
        }

        return \Laravel\Socialite\Facades\Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsConfigured($provider);

        $providerUser = $this->resolveProviderUser($provider);

        if ($providerUser === null) {
            return redirect()->route('login')->withErrors([
                'email' => 'Não foi possível concluir a autenticação com '.ucfirst($provider).'.',
            ]);
        }

        $user = $this->findOrCreateUser($provider, $providerUser);

        if (! $user->status->canAuthenticate()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Esta conta está suspensa.',
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(
            $user->isBusiness() ? route('business.dashboard') : route('consumer.dashboard')
        );
    }

    /**
     * @param  array{id:string,email:?string,email_verified:bool,name:?string,avatar:?string}  $providerUser
     */
    private function findOrCreateUser(string $provider, array $providerUser): User
    {
        $existingLink = SocialAccount::where('provider', $provider)
            ->where('provider_user_id', $providerUser['id'])
            ->first();

        if ($existingLink) {
            return $existingLink->user;
        }

        return DB::transaction(function () use ($provider, $providerUser): User {
            $email = $providerUser['email'] ? mb_strtolower($providerUser['email']) : null;

            $user = $email && $providerUser['email_verified']
                ? User::where('email', $email)->first()
                : null;

            if ($user === null) {
                $user = User::create([
                    'uuid' => (string) Str::uuid(),
                    'type' => UserType::Consumer,
                    'status' => UserStatus::Active,
                    'name' => $providerUser['name'] ?? 'Utilizador',
                    'public_name' => $this->uniquePublicName($providerUser['name'] ?? 'utilizador'),
                    'email' => $email ?? Str::uuid().'@sem-email.queixa.me',
                    'email_verified_at' => $providerUser['email_verified'] ? now() : null,
                    'country' => 'PT',
                ]);

                $this->consents->recordMany([
                    ConsentType::Terms,
                    ConsentType::Privacy,
                    ConsentType::DataProtection,
                ], $user);

                event(new Registered($user));
            }

            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_user_id' => $providerUser['id'],
                'provider_email' => $email,
                'provider_email_verified' => $providerUser['email_verified'],
                'avatar_url' => $providerUser['avatar'] ?? null,
                'linked_at' => now(),
            ]);

            return $user;
        });
    }

    /**
     * @return array{id:string,email:?string,email_verified:bool,name:?string,avatar:?string}|null
     */
    private function resolveProviderUser(string $provider): ?array
    {
        if (! class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
            return null;
        }

        try {
            $socialite = \Laravel\Socialite\Facades\Socialite::driver($provider)->user();
        } catch (\Throwable) {
            return null;
        }

        $raw = method_exists($socialite, 'getRaw') ? (array) $socialite->getRaw() : [];

        return [
            'id' => (string) $socialite->getId(),
            'email' => $socialite->getEmail(),
            // Google devolve email_verified; a Apple devolve is_private_email
            // e considera o email sempre verificado quando o partilha.
            'email_verified' => (bool) ($raw['email_verified'] ?? $raw['verified_email'] ?? $provider === 'apple'),
            'name' => $socialite->getName(),
            'avatar' => $socialite->getAvatar(),
        ];
    }

    private function uniquePublicName(string $seed): string
    {
        $base = Str::slug(Str::before($seed, ' ')) ?: 'utilizador';

        do {
            $candidate = $base.random_int(100, 9999);
        } while (User::where('public_name', $candidate)->exists());

        return $candidate;
    }

    private function ensureProviderIsConfigured(string $provider): void
    {
        abort_unless(filled(config("services.{$provider}.client_id")), 404);
    }
}
