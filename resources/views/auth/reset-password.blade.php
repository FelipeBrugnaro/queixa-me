@extends('layouts.auth')

@section('auth-title', 'Definir nova palavra-passe')

@section('auth-body')
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" data-guard-submit>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-field name="email" label="Endereço de email" type="email" :value="$email" required autocomplete="email" />
        <x-field name="password" label="Nova palavra-passe" type="password" required autocomplete="new-password"
                 hint="Mínimo 10 caracteres, com letras e números." />
        <x-field name="password_confirmation" label="Confirmar nova palavra-passe" type="password" required autocomplete="new-password" />

        <button type="submit" class="btn btn-primary w-full">Alterar palavra-passe</button>
    </form>
@endsection
