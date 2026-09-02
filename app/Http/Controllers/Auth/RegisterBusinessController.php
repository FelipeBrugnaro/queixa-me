<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Accounts\Enums\ConsentType;
use App\Domain\Accounts\Enums\UserStatus;
use App\Domain\Accounts\Enums\UserType;
use App\Domain\Accounts\Models\User;
use App\Domain\Accounts\Services\ConsentRecorder;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Registo de gestor de empresa.
 *
 * Pedimos deliberadamente o mínimo (nome, email, palavra-passe). O perfil da
 * empresa e a prova de ligação à marca são tratados depois, na reivindicação
 * da ficha — pedir NIF e documentos no primeiro ecrã afastaria exatamente as
 * empresas que queremos a responder.
 */
class RegisterBusinessController extends Controller
{
    public function create(): View
    {
        $this->seo()
            ->title('Criar conta de empresa')
            ->description('Regista a tua empresa no queixa.me para receberes e responderes às reclamações dos teus clientes.')
            ->noindex(follow: true);

        return view('auth.register-business');
    }

    public function store(Request $request, ConsentRecorder $consents): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()->uncompromised()],
            'company_name' => ['nullable', 'string', 'max:160'],
            'accept_terms' => ['accepted'],
            'accept_privacy' => ['accepted'],
            'website' => ['nullable', 'size:0'],
        ], [
            'accept_terms.accepted' => 'Tens de aceitar os Termos e Condições.',
            'accept_privacy.accepted' => 'Tens de aceitar a Política de Privacidade.',
            'website.size' => 'Pedido inválido.',
        ]);

        $user = DB::transaction(function () use ($data, $consents): User {
            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'type' => UserType::Business,
                'status' => UserStatus::Active,
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],
                'country' => 'PT',
            ]);

            $consents->recordMany([
                ConsentType::Terms,
                ConsentType::Privacy,
                ConsentType::DataProtection,
            ], $user);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()
            ->route('business.claim.create', ['empresa' => $data['company_name'] ?? null])
            ->with('success', 'Conta criada. Falta associares a tua empresa.');
    }
}
