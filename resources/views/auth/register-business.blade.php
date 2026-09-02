@extends('layouts.auth')

@section('auth-title', 'Criar conta de empresa')
@section('auth-subtitle', 'Pedimos só o essencial. O perfil da empresa e a validação ficam para o passo seguinte.')

@section('auth-body')
    <form method="POST" action="{{ route('register.business.store') }}" class="space-y-5" data-guard-submit>
        @csrf

        <div class="hidden" aria-hidden="true">
            <label for="website">Não preencher</label>
            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>

        <x-field name="name" label="O teu nome" required autofocus autocomplete="name" />
        <x-field name="company_name" label="Nome da empresa" placeholder="ex.: Loja do Bairro, Lda."
                 hint="Ajuda-nos a encontrar a ficha certa. Podes indicar depois." />
        <x-field name="email" label="Email profissional" type="email" required autocomplete="email"
                 hint="Um email no domínio da empresa acelera muito a validação." />
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
        </fieldset>

        <button type="submit" class="btn btn-primary w-full">Criar conta de empresa</button>
    </form>
@endsection

@section('auth-footer')
    És consumidor?
    <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-800">Cria uma conta pessoal</a>
@endsection
