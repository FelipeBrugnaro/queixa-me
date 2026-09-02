@extends('layouts.panel')

@section('panel-heading')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <x-company-avatar :company="$company" size="lg" />
            <div>
                <h1 class="text-2xl font-bold">{{ $company->name }}</h1>
                <a href="{{ $company->url() }}" class="text-sm text-brand-700 hover:text-brand-800">Ver ficha pública</a>
            </div>
        </div>
        <x-index-badge :company="$company" size="lg" />
    </div>
@endsection

@section('panel')

    {{-- Alerta de SLA: o número que a empresa precisa de ver primeiro --}}
    @if ($counters['overdue'] > 0)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-900 ring-1 ring-inset ring-rose-200">
            <p>
                <strong class="font-semibold">{{ $counters['overdue'] }}</strong>
                {{ $counters['overdue'] === 1 ? 'reclamação está' : 'reclamações estão' }}
                sem resposta há mais de {{ config('queixame.complaints.response_sla_days') }} dias.
                Isto está a baixar o vosso índice.
            </p>
            <a href="{{ route('business.complaints.index', ['filtro' => 'atrasadas']) }}" class="btn btn-sm bg-rose-600 text-white hover:bg-rose-700">
                Responder agora
            </a>
        </div>
    @endif

    <dl class="grid grid-cols-2 gap-4 lg:grid-cols-3">
        @foreach ([
            ['Por responder', $counters['awaiting'], 'text-amber-700'],
            ['Em curso', $counters['in_progress'], 'text-indigo-700'],
            ['Resolvidas', $counters['resolved'], 'text-emerald-700'],
            ['Este mês', $counters['this_month'], 'text-ink-900'],
            ['Total publicadas', $counters['total'], 'text-ink-900'],
            ['Mensagens por ler', $unreadMessages, 'text-brand-700'],
        ] as [$label, $value, $color])
            <div class="card">
                <div class="p-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-500">{{ $label }}</dt>
                    <dd class="mt-1 text-2xl font-bold {{ $color }}">{{ $value }}</dd>
                </div>
            </div>
        @endforeach
    </dl>

    {{-- Composição do índice --}}
    @if ($stat)
        <section class="card mt-8" aria-labelledby="indice">
            <div class="card-body">
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <h2 id="indice" class="text-lg font-semibold">O vosso índice, componente a componente</h2>
                    <a href="{{ route('business.stats') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                        Ver detalhe <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>

                <dl class="mt-5 space-y-4">
                    @foreach (($stat->breakdown['components'] ?? []) as $key => $value)
                        @php
                            $labels = [
                                'response_rate' => 'Responder às reclamações',
                                'resolution_rate' => 'Resolver, confirmado pelo consumidor',
                                'satisfaction' => 'Avaliação dos consumidores',
                                'speed' => 'Rapidez da primeira resposta',
                            ];
                        @endphp
                        <div>
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <dt class="text-ink-700">{{ $labels[$key] ?? $key }}</dt>
                                <dd class="font-semibold text-ink-900">{{ $value !== null ? number_format($value, 0) : 'sem dados' }}</dd>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ink-100">
                                <div class="h-full rounded-full {{ ($value ?? 0) >= 70 ? 'bg-emerald-500' : (($value ?? 0) >= 45 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                     style="width: {{ $value !== null ? min(100, (int) $value) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    @endif

    {{-- Fila de trabalho --}}
    <section class="mt-8" aria-labelledby="fila">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 id="fila" class="text-lg font-semibold">Aguardam a vossa resposta</h2>
            <a href="{{ route('business.complaints.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                Ver todas <span aria-hidden="true">&rarr;</span>
            </a>
        </div>

        @if ($pending->isEmpty())
            <x-empty-state
                title="Tudo respondido"
                description="Não há reclamações à espera de resposta. Bom trabalho." />
        @else
            <ul class="space-y-3">
                @foreach ($pending as $complaint)
                    <li class="card card-hover">
                        <a href="{{ route('business.complaints.show', $complaint->uuid) }}" class="block p-5">
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                @if ($complaint->responseSlaBreached())
                                    <span class="badge bg-rose-50 text-rose-700 ring-rose-200">
                                        Sem resposta há {{ $complaint->daysWaitingForReply() }} dias
                                    </span>
                                @else
                                    <span class="badge bg-amber-50 text-amber-700 ring-amber-200">
                                        Publicada há {{ $complaint->daysWaitingForReply() }} dias
                                    </span>
                                @endif
                                @if ($complaint->category)
                                    <span class="text-ink-400">{{ $complaint->category->name }}</span>
                                @endif
                                <span class="ml-auto text-ink-400">{{ $complaint->reference }}</span>
                            </div>
                            <p class="mt-2 font-medium text-ink-900">{{ $complaint->title }}</p>
                            <p class="mt-1 line-clamp-2 text-sm text-ink-600">{{ $complaint->excerpt(160) }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
