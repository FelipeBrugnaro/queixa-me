@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Administração</h1>
    <p class="mt-1 text-sm text-ink-600">Estado das filas e saúde geral do portal.</p>
@endsection

@section('panel')

    {{-- Pedidos RGPD fora de prazo são risco regulatório, não atraso operacional --}}
    @if ($queue['data_requests_overdue'] > 0)
        <div class="mb-6 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-900 ring-1 ring-inset ring-rose-200">
            <strong class="font-semibold">{{ $queue['data_requests_overdue'] }}</strong>
            {{ $queue['data_requests_overdue'] === 1 ? 'pedido RGPD está' : 'pedidos RGPD estão' }}
            fora do prazo legal de 30 dias.
        </div>
    @endif

    {{-- Filas de trabalho --}}
    <section aria-labelledby="filas">
        <h2 id="filas" class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500">Filas de trabalho</h2>
        <dl class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            {{-- Classes de cor escritas por extenso: o Tailwind analisa o
                 código estaticamente e não gera classes construídas em runtime. --}}
            @foreach ([
                ['Moderação', $queue['moderation'], route('admin.moderation.index'), 'text-amber-700'],
                ['Com dados sensíveis', $queue['sensitive'], route('admin.moderation.index', ['sensiveis' => 1]), 'text-rose-700'],
                ['Denúncias abertas', $queue['reports'], route('admin.reports.index'), 'text-rose-700'],
                ['Reivindicações', $queue['claims'], route('admin.companies.index'), 'text-brand-700'],
                ['Empresas por validar', $queue['companies'], route('admin.companies.index', ['estado' => 'pending']), 'text-brand-700'],
                ['Pedidos RGPD', $queue['data_requests'], null, 'text-ink-900'],
            ] as [$label, $value, $url, $tone])
                <div class="card">
                    <div class="p-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-ink-500">{{ $label }}</dt>
                        <dd class="mt-1 flex items-baseline gap-2">
                            <span class="text-2xl font-bold {{ $value > 0 ? $tone : 'text-ink-400' }}">{{ $value }}</span>
                            @if ($url && $value > 0)
                                <a href="{{ $url }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">abrir</a>
                            @endif
                        </dd>
                    </div>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Empresas fora do prazo de resposta --}}
    @if ($slaBreaches > 0)
        <div class="mt-6 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
            <strong class="font-semibold">{{ $slaBreaches }}</strong>
            {{ $slaBreaches === 1 ? 'reclamação publicada está' : 'reclamações publicadas estão' }}
            sem resposta da empresa há mais de {{ config('queixame.complaints.response_sla_days') }} dias.
        </div>
    @endif

    {{-- Totais --}}
    <section class="mt-8" aria-labelledby="totais">
        <h2 id="totais" class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500">Totais do portal</h2>
        <dl class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            @foreach ([
                ['Reclamações', $totals['complaints']],
                ['Publicadas', $totals['published']],
                ['Resolvidas', $totals['resolved']],
                ['Empresas', $totals['companies']],
                ['Utilizadores', $totals['users']],
            ] as [$label, $value])
                <div class="card">
                    <div class="p-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-ink-500">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-bold text-ink-900">{{ number_format($value, 0, ',', ' ') }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Próximos na fila --}}
    <section class="mt-8" aria-labelledby="proximos">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 id="proximos" class="text-lg font-semibold">Próximos na fila de moderação</h2>
            <a href="{{ route('admin.moderation.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                Ver fila <span aria-hidden="true">&rarr;</span>
            </a>
        </div>

        @if ($recent->isEmpty())
            <x-empty-state title="Fila vazia" description="Não há reclamações à espera de análise." />
        @else
            <ul class="space-y-3">
                @foreach ($recent as $complaint)
                    <li class="card card-hover {{ $complaint->sensitive_flags ? 'ring-rose-200' : '' }}">
                        <a href="{{ route('admin.moderation.show', $complaint->uuid) }}" class="block p-5">
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="badge {{ $complaint->moderation_status->badgeClasses() }}">
                                    {{ $complaint->moderation_status->label() }}
                                </span>
                                @if ($complaint->sensitive_flags)
                                    <span class="badge bg-rose-50 text-rose-700 ring-rose-200">
                                        Dados sensíveis detetados
                                    </span>
                                @endif
                                <span class="text-ink-400">{{ $complaint->company?->name }}</span>
                                <span class="ml-auto text-ink-400">
                                    {{ $complaint->submitted_at?->diffForHumans() }}
                                </span>
                            </div>
                            <p class="mt-2 font-medium text-ink-900">{{ $complaint->title }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
