@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Olá, {{ $user->first_name ?: $user->publicDisplayName() }}.</h1>
    <p class="mt-1 text-sm text-ink-600">Aqui está o estado das tuas reclamações.</p>
@endsection

@section('panel')

    {{-- Ação necessária primeiro: é o que o utilizador veio cá fazer --}}
    @if ($actionRequired->isNotEmpty())
        <section class="mb-8" aria-labelledby="accao">
            <h2 id="accao" class="mb-3 text-sm font-semibold uppercase tracking-wide text-amber-700">
                Precisa da tua atenção
            </h2>
            <ul class="space-y-3">
                @foreach ($actionRequired as $complaint)
                    <li class="card ring-amber-200">
                        <div class="card-body flex flex-wrap items-center gap-4">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-ink-900">{{ $complaint->title ?: 'Reclamação '.$complaint->reference }}</p>
                                <p class="mt-0.5 text-sm text-ink-500">
                                    {{ $complaint->company?->name }}
                                    <span aria-hidden="true">·</span>
                                    @if ($complaint->moderation_status->value === 'changes_requested')
                                        A nossa equipa pediu alterações
                                    @else
                                        A empresa propôs uma solução — confirma se ficou resolvido
                                    @endif
                                </p>
                            </div>
                            <a href="{{ $complaint->moderation_status->isEditableByAuthor()
                                        ? route('complaints.wizard.description', $complaint->uuid)
                                        : route('consumer.complaints.show', $complaint->uuid) }}"
                               class="btn btn-primary btn-sm">
                                {{ $complaint->moderation_status->isEditableByAuthor() ? 'Corrigir' : 'Responder' }}
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($drafts->isNotEmpty())
        <section class="mb-8" aria-labelledby="rascunhos">
            <h2 id="rascunhos" class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500">Rascunhos</h2>
            <ul class="space-y-3">
                @foreach ($drafts as $draft)
                    <li class="card">
                        <div class="card-body flex flex-wrap items-center gap-4">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-ink-900">
                                    {{ $draft->title ?: 'Reclamação sem título' }}
                                </p>
                                <p class="mt-0.5 text-sm text-ink-500">
                                    {{ $draft->company?->name ?? $draft->company_name_raw }}
                                    <span aria-hidden="true">·</span> começado {{ $draft->created_at?->diffForHumans() }}
                                </p>
                            </div>
                            <a href="{{ route('complaints.wizard.description', $draft->uuid) }}" class="btn btn-secondary btn-sm">Continuar</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Contadores --}}
    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        @foreach ([
            ['Total', $counters['total'], 'text-ink-900'],
            ['Em análise', $counters['pending'], 'text-amber-700'],
            ['Publicadas', $counters['published'], 'text-brand-700'],
            ['Com resposta', $counters['replied'], 'text-indigo-700'],
            ['Resolvidas', $counters['resolved'], 'text-emerald-700'],
            ['Rejeitadas', $counters['rejected'], 'text-rose-700'],
        ] as [$label, $value, $color])
            <div class="card">
                <div class="p-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-500">{{ $label }}</dt>
                    <dd class="mt-1 text-2xl font-bold {{ $color }}">{{ $value }}</dd>
                </div>
            </div>
        @endforeach
    </dl>

    {{-- Reclamações recentes --}}
    <section class="mt-8" aria-labelledby="recentes">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 id="recentes" class="text-lg font-semibold">Reclamações recentes</h2>
            <a href="{{ route('consumer.complaints.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                Ver todas <span aria-hidden="true">&rarr;</span>
            </a>
        </div>

        @if ($complaints->isEmpty())
            <x-empty-state
                title="Ainda não tens reclamações"
                description="Quando algo corre mal, contar o que aconteceu é o primeiro passo para resolver.">
                <a href="{{ route('complaints.create') }}" class="btn btn-primary">Fazer a primeira reclamação</a>
            </x-empty-state>
        @else
            <ul class="space-y-3">
                @foreach ($complaints as $complaint)
                    <li class="card card-hover">
                        <a href="{{ route('consumer.complaints.show', $complaint->uuid) }}" class="block p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge {{ $complaint->moderation_status->badgeClasses() }}">{{ $complaint->moderation_status->label() }}</span>
                                @if ($complaint->isPublished())
                                    <span class="badge {{ $complaint->stage->badgeClasses() }}">{{ $complaint->stage->label() }}</span>
                                @endif
                                <span class="ml-auto text-xs text-ink-400">{{ $complaint->reference }}</span>
                            </div>
                            <p class="mt-2 font-medium text-ink-900">{{ $complaint->title ?: 'Sem título' }}</p>
                            <p class="mt-0.5 text-sm text-ink-500">
                                {{ $complaint->company?->name ?? $complaint->company_name_raw }}
                                <span aria-hidden="true">·</span> {{ $complaint->created_at?->translatedFormat('j M Y') }}
                            </p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <div class="mt-8 rounded-2xl bg-ink-900 px-6 py-8 text-center">
        <h2 class="text-lg font-semibold text-white">Tens outro problema por resolver?</h2>
        <a href="{{ route('complaints.create') }}" class="btn btn-primary mt-4">Fazer uma reclamação</a>
    </div>
@endsection
