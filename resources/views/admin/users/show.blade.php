@extends('layouts.panel')

@section('panel-heading')
    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-ink-500 hover:text-ink-800">
        <span aria-hidden="true">&larr;</span> Utilizadores
    </a>
    <h1 class="mt-2 text-2xl font-bold">{{ $user->publicDisplayName() }}</h1>
    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
        <span class="badge bg-ink-100 text-ink-700 ring-ink-200">{{ $user->type->label() }}</span>
        <span class="badge {{ $user->isActive() ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
            {{ $user->status->label() }}
        </span>
        <span class="text-ink-400">{{ $user->email }}</span>
    </div>
@endsection

@section('panel')
<div class="lg:grid lg:grid-cols-[1fr_18rem] lg:gap-6">

    <div class="min-w-0 space-y-6">
        <dl class="grid grid-cols-3 gap-4">
            @foreach ([
                ['Reclamações', $stats['total']],
                ['Últimos 30 dias', $stats['last_30_days']],
                ['Rejeitadas', $stats['rejected']],
            ] as [$label, $value])
                <div class="card">
                    <div class="p-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-ink-500">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-bold text-ink-900">{{ $value }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>

        @if ($stats['rejected'] > 2 || $stats['last_30_days'] > 8)
            <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
                Padrão a verificar: volume ou taxa de rejeição acima do habitual.
            </div>
        @endif

        {{-- Reclamações --}}
        <section class="card" aria-labelledby="reclamacoes">
            <div class="card-body">
                <h2 id="reclamacoes" class="font-semibold">Reclamações submetidas</h2>

                @if ($complaints->isEmpty())
                    <p class="mt-2 text-sm text-ink-500">Nenhuma.</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($complaints as $complaint)
                            <li class="rounded-lg bg-ink-50 p-3">
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <span class="badge {{ $complaint->moderation_status->badgeClasses() }}">
                                        {{ $complaint->moderation_status->label() }}
                                    </span>
                                    <span class="text-ink-500">{{ $complaint->company?->name }}</span>
                                    <span class="ml-auto text-ink-400">{{ $complaint->created_at?->translatedFormat('j M Y') }}</span>
                                </div>
                                <p class="mt-1.5 text-sm font-medium text-ink-800">
                                    {{ $complaint->title ?: 'Sem título' }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        {{-- Consentimentos --}}
        @if ($user->consents->isNotEmpty())
            <section class="card" aria-labelledby="consentimentos">
                <div class="card-body">
                    <h2 id="consentimentos" class="font-semibold">Consentimentos registados</h2>
                    <ul class="mt-3 space-y-1.5 text-sm">
                        @foreach ($user->consents as $consent)
                            <li class="flex items-center justify-between gap-3 rounded-lg bg-ink-50 px-3 py-2">
                                <span class="text-ink-700">{{ $consent->type->label() }} <span class="text-xs text-ink-400">v{{ $consent->document_version }}</span></span>
                                <span class="text-xs text-ink-400">{{ $consent->granted_at?->translatedFormat('j M Y, H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif
    </div>

    {{-- Ações --}}
    <aside class="mt-6 space-y-4 lg:mt-0">
        @if ($user->companies->isNotEmpty())
            <div class="card">
                <div class="card-body">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-500">Empresas geridas</h2>
                    <ul class="mt-3 space-y-1.5 text-sm">
                        @foreach ($user->companies as $company)
                            <li>
                                <a href="{{ route('admin.companies.edit', $company) }}" class="text-brand-700 hover:text-brand-800">
                                    {{ $company->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if ($user->isActive())
            <form method="POST" action="{{ route('admin.users.block', $user) }}" class="card ring-rose-200" data-guard-submit>
                @csrf
                <div class="card-body">
                    <h2 class="text-sm font-semibold text-rose-900">Bloquear conta</h2>
                    <input type="text" name="reason" required maxlength="500" placeholder="Motivo"
                           class="input mt-3 text-sm">
                    <button type="submit" class="btn btn-danger btn-sm mt-3 w-full">Bloquear</button>
                </div>
            </form>
        @else
            <form method="POST" action="{{ route('admin.users.unblock', $user) }}" class="card">
                @csrf
                <div class="card-body">
                    <h2 class="text-sm font-semibold">Conta bloqueada</h2>
                    @if ($user->blocked_reason)
                        <p class="mt-1.5 text-xs text-ink-500">{{ $user->blocked_reason }}</p>
                    @endif
                    <button type="submit" class="btn btn-secondary btn-sm mt-3 w-full">Desbloquear</button>
                </div>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.users.anonymise', $user) }}" class="card ring-rose-300" data-guard-submit>
            @csrf
            <div class="card-body">
                <h2 class="text-sm font-semibold text-rose-900">Anonimizar (RGPD)</h2>
                <p class="mt-1.5 text-xs leading-relaxed text-ink-600">
                    Apaga todos os dados pessoais e a ligação às reclamações. O texto público
                    mantém-se, sem titular identificável. Irreversível.
                </p>
                <button type="submit" class="btn btn-danger btn-sm mt-3 w-full">Anonimizar conta</button>
            </div>
        </form>
    </aside>
</div>
@endsection
