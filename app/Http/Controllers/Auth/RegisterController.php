<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Accounts\Actions\RegisterConsumer;
use App\Domain\Accounts\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterConsumerRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create(): View
    {
        $this->seo()
            ->title('Criar conta de consumidor')
            ->description('Cria a tua conta no queixa.me para apresentares reclamações e acompanhares as respostas das empresas.')
            ->noindex(follow: true);

        return view('auth.register', [
            'genders' => Gender::options(),
            'socialEnabled' => $this->socialProviders(),
        ]);
    }

    public function store(RegisterConsumerRequest $request, RegisterConsumer $action): RedirectResponse
    {
        $user = $action->handle($request->validated());

        event(new Registered($user));

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()
            ->intended(route('verification.notice'))
            ->with('success', 'Conta criada. Enviámos um email para confirmares o teu endereço.');
    }

    /** @return array<int,string> */
    private function socialProviders(): array
    {
        return collect(['google', 'apple'])
            ->filter(fn (string $provider) => filled(config("services.{$provider}.client_id")))
            ->values()
            ->all();
    }
}
