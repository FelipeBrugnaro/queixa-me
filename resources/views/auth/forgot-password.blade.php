@extends('layouts.auth')

@section('auth-title', 'Recuperar palavra-passe')
@section('auth-subtitle', 'Indica o teu email e enviamos-te um link para definires uma nova palavra-passe.')

@section('auth-body')
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" data-guard-submit>
        @csrf
        <x-field name="email" label="Endereço de email" type="email" required autofocus autocomplete="email" />
        <button type="submit" class="btn btn-primary w-full">Enviar link de recuperação</button>
    </form>
@endsection

@section('auth-footer')
    <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800">Voltar ao início de sessão</a>
@endsection
