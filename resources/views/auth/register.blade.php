@extends('layouts.auth')

@section('auth-title', 'Criar conta')
@section('auth-subtitle', 'Precisas de conta para reclamares — é assim que garantimos que cada reclamação tem uma pessoa real por trás.')

@section('auth-body')
    <form method="POST" action="{{ route('register.store') }}" class="space-y-5" data-guard-submit>
        @csrf

        {{-- Campo isco: escondido de pessoas, visível para robôs --}}
        <div class="hidden" aria-hidden="true">
            <label for="website">Não preencher</label>
            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>

        <div class="rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-900 ring-1 ring-inset ring-brand-100">
            <p class="font-semibold">O teu nome público é o único que aparece no site.</p>
            <p class="mt-1 text-brand-800">O nome próprio e o apelido nunca são publicados: são transmitidos apenas à empresa visada, e só com o teu consentimento em cada reclamação.</p>
        </div>

        <x-field name="public_name" label="Nome público" required autofocus
                 placeholder="ex.: joaom_87"
                 hint="Entre {{ config('queixame.accounts.public_name_min') }} e {{ config('queixame.accounts.public_name_max') }} caracteres. É o que aparece nas reclamações." />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="first_name" label="Nome próprio" required autocomplete="given-name" />
            <x-field name="last_name" label="Apelido" required autocomplete="family-name" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="birthdate" label="Data de nascimento" type="date" required
                     max="{{ now()->toDateString() }}"
                     hint="Mínimo {{ config('queixame.accounts.minimum_age') }} anos." />
            <x-field name="gender" label="Género" type="select" required :options="$genders" />
        </div>

        <x-field name="email" label="Endereço de email" type="email" required autocomplete="email"
                 hint="Vamos enviar-te um email para confirmares o endereço." />

        <x-field name="password" label="Palavra-passe" type="password" required autocomplete="new-password"
                 hint="Mínimo 10 caracteres, com letras e números." />
        <x-field name="password_confirmation" label="Confirmar palavra-passe" type="password" required autocomplete="new-password" />

        <fieldset class="space-y-3 rounded-xl bg-ink-50 p-4">
            <legend class="sr-only">Consentimentos</legend>

            <label class="flex items-start gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="accept_terms" value="1" class="checkbox" @checked(old('accept_terms')) required>
                <span>Li e aceito os <a href="{{ route('legal.terms') }}" target="_blank" class="font-medium text-brand-700 underline underline-offset-2">Termos e Condições</a>.</span>
            </label>
            @error('accept_terms')<p class="error-text">{{ $message }}</p>@enderror

            <label class="flex items-start gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="accept_privacy" value="1" class="checkbox" @checked(old('accept_privacy')) required>
                <span>Li e aceito a <a href="{{ route('legal.privacy') }}" target="_blank" class="font-medium text-brand-700 underline underline-offset-2">Política de Privacidade</a>.</span>
            </label>
            @error('accept_privacy')<p class="error-text">{{ $message }}</p>@enderror

            <label class="flex items-start gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="accept_data_protection" value="1" class="checkbox" @checked(old('accept_data_protection')) required>
                <span>Li e aceito a <a href="{{ route('legal.data-protection') }}" target="_blank" class="font-medium text-brand-700 underline underline-offset-2">Política de Proteção de Dados</a>.</span>
            </label>
            @error('accept_data_protection')<p class="error-text">{{ $message }}</p>@enderror

            <hr class="border-ink-200">

            <label class="flex items-start gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="marketing_opt_in" value="1" class="checkbox" @checked(old('marketing_opt_in'))>
                <span>Quero receber notícias e novidades por email. <span class="text-ink-500">(podes cancelar a qualquer momento)</span></span>
            </label>
        </fieldset>

        <button type="submit" class="btn btn-primary w-full">Criar conta</button>
    </form>

    @if ($socialEnabled)
        <div class="my-6 flex items-center gap-3">
            <span class="h-px flex-1 bg-ink-200"></span>
            <span class="text-xs font-medium uppercase tracking-wide text-ink-400">ou</span>
            <span class="h-px flex-1 bg-ink-200"></span>
        </div>

        <div class="space-y-2">
            @foreach ($socialEnabled as $provider)
                <a href="{{ route('social.redirect', $provider) }}" class="btn btn-secondary w-full">
                    Continuar com {{ ucfirst($provider) }}
                </a>
            @endforeach
        </div>
    @endif
@endsection

@section('auth-footer')
    Já tens conta?
    <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800">Entrar</a>
@endsection
