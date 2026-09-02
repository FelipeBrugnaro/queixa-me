@extends('layouts.auth')

@section('auth-title', 'Confirma o teu email')
@section('auth-subtitle', 'Enviámos um link de confirmação para o endereço que indicaste.')

@section('auth-body')
    <div class="prose-qm text-sm">
        <p>Sem confirmar o email não conseguimos garantir que as reclamações têm origem numa pessoa contactável — e é isso que as torna credíveis perante as empresas.</p>
        <p class="text-ink-500">Não recebeste? Verifica a pasta de spam antes de pedires um novo envio.</p>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6" data-guard-submit>
        @csrf
        <button type="submit" class="btn btn-primary w-full">Reenviar email de confirmação</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-ghost w-full">Terminar sessão</button>
    </form>
@endsection
