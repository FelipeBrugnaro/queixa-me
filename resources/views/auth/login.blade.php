@extends('layouts.auth')

@section('auth-title', 'Entrar')
@section('auth-subtitle', 'Acede à tua conta para acompanhares as tuas reclamações.')

@section('auth-body')
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5" data-guard-submit>
        @csrf

        <x-field name="email" label="Endereço de email" type="email" required autocomplete="email" autofocus />
        <x-field name="password" label="Palavra-passe" type="password" required autocomplete="current-password" />

        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-ink-600">
                <input type="checkbox" name="remember" value="1" class="checkbox" @checked(old('remember'))>
                Manter sessão iniciada
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">
                Esqueceste-te?
            </a>
        </div>

        <button type="submit" class="btn btn-primary w-full">Entrar</button>
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
    Ainda não tens conta?
    <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-800">Criar conta</a>
    <span class="mx-1 text-ink-300">·</span>
    <a href="{{ route('register.business') }}" class="font-semibold text-brand-700 hover:text-brand-800">Sou uma empresa</a>
@endsection
