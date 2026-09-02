<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Accounts\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        $this->seo()->title('Recuperar palavra-passe')->noindex(follow: true);

        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:190']]);

        PasswordBroker::sendResetLink(['email' => mb_strtolower((string) $request->input('email'))]);

        // Resposta sempre igual, exista ou não a conta: caso contrário este
        // formulário torna-se um verificador de emails registados.
        return back()->with(
            'success',
            'Se existir uma conta com esse endereço, enviámos as instruções para recuperares a palavra-passe.'
        );
    }

    public function edit(Request $request, string $token): View
    {
        $this->seo()->title('Definir nova palavra-passe')->noindex(follow: false);

        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()->uncompromised()],
        ]);

        $status = PasswordBroker::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        return $status === PasswordBroker::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Palavra-passe alterada. Já podes entrar.')
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => 'O link de recuperação é inválido ou expirou.']);
    }
}
