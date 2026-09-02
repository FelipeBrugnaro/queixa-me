<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Accounts\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create(Request $request): View
    {
        $this->seo()
            ->title('Entrar')
            ->description('Entra na tua conta do queixa.me.')
            ->noindex(follow: true);

        return view('auth.login', [
            'socialEnabled' => collect(['google', 'apple'])
                ->filter(fn (string $p) => filled(config("services.{$p}.client_id")))
                ->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $user = User::where('email', mb_strtolower($credentials['email']))->first();

        if (! $user || ! Auth::attempt(
            ['email' => mb_strtolower($credentials['email']), 'password' => $credentials['password']],
            (bool) $request->boolean('remember'),
        )) {
            RateLimiter::hit($this->throttleKey($request), 600);
            $this->logAttempt($request, false);

            // Mensagem genérica: distinguir "email não existe" de "palavra-passe
            // errada" permitiria enumerar contas do portal.
            throw ValidationException::withMessages([
                'email' => 'As credenciais indicadas não correspondem aos nossos registos.',
            ]);
        }

        if (! $user->status->canAuthenticate()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Esta conta está suspensa. Contacta-nos para mais informações.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $this->logAttempt($request, true);

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        return redirect()->intended(
            $user->isBusiness() ? route('business.dashboard') : route('consumer.dashboard')
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Sessão terminada.');
    }

    /**
     * Bloqueio progressivo por combinação email + IP. Bloquear apenas por
     * email permitiria a um atacante trancar a conta de outra pessoa.
     */
    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => 'Demasiadas tentativas. Tenta novamente dentro de '.ceil($seconds / 60).' minutos.',
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return 'login:'.mb_strtolower((string) $request->input('email')).'|'.$request->ip();
    }

    private function logAttempt(Request $request, bool $successful): void
    {
        rescue(fn () => DB::table('login_attempts')->insert([
            'email' => mb_substr((string) $request->input('email'), 0, 190),
            'ip_address' => $request->ip(),
            'successful' => $successful,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]), report: false);
    }
}
