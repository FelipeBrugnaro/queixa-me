<?php

declare(strict_types=1);

namespace App\Http\Controllers\Consumer;

use App\Domain\Accounts\Enums\Gender;
use App\Domain\Accounts\Services\EmailChangeService;
use App\Domain\Accounts\Services\PhoneVerificationService;
use App\Domain\Shared\Rules\AllowedPublicName;
use App\Domain\Shared\Rules\MinimumAge;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class ProfileController extends Controller
{
    public function __construct(
        private readonly EmailChangeService $emailChanges,
        private readonly PhoneVerificationService $phones,
    ) {}

    public function edit(Request $request): View
    {
        $this->seo()->title('Perfil');

        return view('consumer.profile.edit', [
            'user' => $request->user(),
            'genders' => Gender::options(),
            'pendingEmailChange' => $this->emailChanges->pendingFor($request->user()),
            'socialAccounts' => $request->user()->socialAccounts,
            'districts' => $this->districts(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'public_name' => [
                'required', 'string',
                'min:'.config('queixame.accounts.public_name_min'),
                'max:'.config('queixame.accounts.public_name_max'),
                Rule::unique('users', 'public_name')->ignore($user->id),
                new AllowedPublicName,
            ],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'birthdate' => ['nullable', 'date', new MinimumAge],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'country' => ['nullable', 'string', 'size:2'],
            'district' => ['nullable', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:120'],
        ], [], [
            'public_name' => 'nome público',
            'first_name' => 'nome próprio',
            'last_name' => 'apelido',
        ]);

        $user->fill($data);
        $user->name = trim($data['first_name'].' '.$data['last_name']);
        $user->save();

        return back()->with('success', 'Perfil atualizado.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ], [], ['avatar' => 'fotografia']);

        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars/'.$user->uuid, 'public');
        $user->forceFill(['avatar_path' => $path])->save();

        return back()->with('success', 'Fotografia atualizada.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return back()->with('success', 'Fotografia removida.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()->uncompromised()],
        ], [
            'current_password.current_password' => 'A palavra-passe atual não está correta.',
        ]);

        $request->user()->forceFill(['password' => $data['password']])->save();

        // Invalidar as restantes sessões: se a alteração foi motivada por
        // suspeita de acesso indevido, deixar as outras sessões abertas
        // anularia o efeito da mudança.
        Auth::logoutOtherDevices($data['password']);

        return back()->with('success', 'Palavra-passe alterada. As sessões noutros dispositivos foram terminadas.');
    }

    // ---------------------------------------------------------------
    // Email
    // ---------------------------------------------------------------

    public function requestEmailChange(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'new_email' => ['required', 'email:rfc,dns', 'max:190'],
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.current_password' => 'Confirma a tua palavra-passe atual para alterares o email.',
        ], ['new_email' => 'novo email']);

        try {
            $this->emailChanges->request($request->user(), $data['new_email'], $request->ip());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['new_email' => $exception->getMessage()]);
        }

        return back()->with(
            'success',
            'Enviámos um link de confirmação para o novo endereço. O email atual mantém-se ativo até confirmares.'
        );
    }

    public function confirmEmailChange(string $token): RedirectResponse
    {
        try {
            $this->emailChanges->confirm($token);
        } catch (RuntimeException $exception) {
            return redirect()->route('consumer.profile.edit')->with('error', $exception->getMessage());
        }

        return redirect()->route('consumer.profile.edit')->with('success', 'Email alterado com sucesso.');
    }

    public function cancelEmailChange(Request $request): RedirectResponse
    {
        $this->emailChanges->cancel($request->user());

        return back()->with('success', 'Pedido de alteração de email cancelado.');
    }

    // ---------------------------------------------------------------
    // Telefone
    // ---------------------------------------------------------------

    public function requestPhoneVerification(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9 ]{9,20}$/'],
        ], [
            'phone.regex' => 'Indica um número de telefone válido.',
        ], ['phone' => 'contacto telefónico']);

        try {
            $this->phones->request($request->user(), $data['phone']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['phone' => $exception->getMessage()]);
        }

        return back()->with('info', 'Enviámos um código por SMS. Introduz o código para confirmares o número.');
    }

    public function confirmPhone(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [], ['code' => 'código']);

        try {
            $this->phones->confirm($request->user(), $data['code']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()->with('success', 'Número de telefone confirmado.');
    }

    /** @return array<int,string> */
    private function districts(): array
    {
        return [
            'Aveiro', 'Beja', 'Braga', 'Bragança', 'Castelo Branco', 'Coimbra', 'Évora',
            'Faro', 'Guarda', 'Leiria', 'Lisboa', 'Portalegre', 'Porto', 'Santarém',
            'Setúbal', 'Viana do Castelo', 'Vila Real', 'Viseu',
            'Região Autónoma dos Açores', 'Região Autónoma da Madeira',
        ];
    }
}
