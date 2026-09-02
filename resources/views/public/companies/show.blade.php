@extends('layouts.app')

@php
    use App\Domain\Complaints\Enums\ComplaintStage;
    use App\Http\Controllers\PublicSite\CompareController;

    $maxHistory = $history->max('satisfaction_index') ?: 100;
@endphp

@section('content')
<div class="container-page py-8">

    {{-- Cabeçalho da empresa --}}
    <header class="card">
        <div class="card-body">
            <div class="flex flex-wrap items-start gap-5">
                <x-company-avatar :company="$company" size="xl" />

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold sm:text-3xl">{{ $company->name }}</h1>
                        @if ($company->verified_at)
                            <span class="badge bg-brand-50 text-brand-700 ring-brand-200" title="Ficha reivindicada e validada pela empresa">
                                <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.7 6.3a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4 0l-3-3a1 1 0 1 1 1.4-1.4L9 12.6l6.3-6.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/>
                                </svg>
                                Perfil verificado
                            </span>
                        @endif
                    </div>

                    <p class="mt-1.5 text-sm text-ink-500">
                        @if ($company->category)
                            <a href="{{ route('companies.category', $company->category) }}" class="hover:text-brand-700">{{ $company->category->name }}</a>
                        @endif
                        @if ($company->district)
                            <span aria-hidden="true">·</span> {{ $company->district }}
                        @endif
                        @if ($company->website)
                            <span aria-hidden="true">·</span>
                            <a href="{{ $company->website }}" rel="nofollow noopener" target="_blank" class="hover:text-brand-700">Website</a>
                        @endif
                    </p>

                    @if ($company->description)
                        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink-600">{{ $company->description }}</p>
                    @endif
                </div>

                <div class="flex flex-col items-stretch gap-2 sm:w-52">
                    <x-index-badge :company="$company" size="lg" />
                    <a href="{{ route('complaints.create', ['empresa' => $company->name]) }}" class="btn btn-primary">Reclamar desta empresa</a>
                    <a href="{{ route('compare.show', ['empresas' => $company->slug]) }}" class="btn btn-secondary btn-sm">Comparar com outra</a>
                </div>
            </div>
        </div>
    </header>

    @unless ($company->hasEnoughDataForIndex())
        <div class="mt-4 rounded-xl bg-ink-100 px-4 py-3 text-sm text-ink-700">
            Esta empresa ainda tem poucas reclamações publicadas para um índice estatisticamente fiável.
            Mostramos os números em bruto, mas não a colocamos no ranking —
            <a href="{{ route('methodology') }}" class="font-medium underline underline-offset-2">porquê?</a>
        </div>
    @endunless

    {{-- Indicadores --}}
    <section class="mt-8" aria-labelledby="indicadores">
        <h2 id="indicadores" class="sr-only">Indicadores</h2>
        <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                ['Reclamações publicadas', number_format($company->published_complaints_count, 0, ',', ' '), 'Total no portal'],
                ['Taxa de resposta', $company->response_rate !== null ? number_format($company->response_rate, 0).'%' : '—', 'Reclamações respondidas'],
                ['Taxa de resolução', $company->resolution_rate !== null ? number_format($company->resolution_rate, 0).'%' : '—', 'Confirmada pelo consumidor'],
                ['Tempo médio de resposta', $company->avg_first_response_minutes !== null ? CompareController::humanDuration($company->avg_first_response_minutes) : '—', 'Até à primeira resposta'],
            ] as [$label, $value, $hint])
                <div class="card">
                    <div class="p-5">
                        <dt class="text-xs font-medium uppercase tracking-wide text-ink-500">{{ $label }}</dt>
                        <dd class="mt-1.5 text-2xl font-bold text-ink-900">{{ $value }}</dd>
                        <p class="mt-0.5 text-xs text-ink-400">{{ $hint }}</p>
                    </div>
                </div>
            @endforeach
        </dl>
    </section>

    <div class="mt-8 lg:grid lg:grid-cols-[1fr_20rem] lg:gap-8">
        <div class="min-w-0 space-y-8">

            {{-- Evolução --}}
            @if ($history->isNotEmpty())
                <section class="card" aria-labelledby="evolucao">
                    <div class="card-body">
                        <h2 id="evolucao" class="text-lg font-semibold">Evolução do índice</h2>
                        <p class="mt-1 text-sm text-ink-500">Últimos {{ $history->count() }} meses.</p>

                        {{-- Gráfico de barras em HTML puro: sem JavaScript, sem
                             biblioteca externa e legível por leitores de ecrã. --}}
                        <div class="mt-6 flex items-end gap-1.5 sm:gap-2" role="img"
                             aria-label="Evolução mensal do índice de satisfação de {{ $company->name }}">
                            @foreach ($history as $stat)
                                @php $height = $stat->satisfaction_index ? max(4, (int) round($stat->satisfaction_index / $maxHistory * 100)) : 2; @endphp
                                <div class="flex flex-1 flex-col items-center gap-1.5">
                                    <span class="text-[10px] font-medium text-ink-500">
                                        {{ $stat->satisfaction_index !== null ? number_format($stat->satisfaction_index, 0) : '—' }}
                                    </span>
                                    <div class="w-full rounded-t bg-brand-500/80" style="height: {{ $height }}px; min-height: 2px;"
                                         title="{{ $stat->period_start->translatedFormat('F Y') }}: {{ $stat->satisfaction_index ?? 'sem dados' }}"></div>
                                    <span class="text-[10px] text-ink-400">{{ $stat->period_start->translatedFormat('M') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            {{-- Composição do índice --}}
            @if ($breakdown && $breakdown->breakdown)
                <section class="card" aria-labelledby="composicao">
                    <div class="card-body">
                        <h2 id="composicao" class="text-lg font-semibold">Como se compõe este índice</h2>
                        <p class="mt-1 text-sm text-ink-500">
                            Índice bruto: {{ $breakdown->raw_index !== null ? number_format($breakdown->raw_index, 1) : '—' }} ·
                            Índice final (com correção estatística): {{ $breakdown->satisfaction_index !== null ? number_format($breakdown->satisfaction_index, 1) : '—' }}
                        </p>

                        <dl class="mt-5 space-y-4">
                            @foreach (($breakdown->breakdown['components'] ?? []) as $key => $value)
                                @php
                                    $labels = [
                                        'response_rate' => 'Responde às reclamações',
                                        'resolution_rate' => 'Resolve, confirmado pelo consumidor',
                                        'satisfaction' => 'Avaliação dos consumidores',
                                        'speed' => 'Rapidez da primeira resposta',
                                    ];
                                    $weight = ($breakdown->breakdown['weights'][$key] ?? 0) * 100;
                                @endphp
                                <div>
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        <dt class="text-ink-700">{{ $labels[$key] ?? $key }}
                                            <span class="text-xs text-ink-400">({{ number_format($weight, 0) }}% do peso)</span>
                                        </dt>
                                        <dd class="font-semibold text-ink-900">{{ $value !== null ? number_format($value, 0) : '—' }}</dd>
                                    </div>
                                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ink-100">
                                        <div class="h-full rounded-full bg-brand-500" style="width: {{ $value !== null ? min(100, (int) $value) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </dl>

                        <p class="mt-5 text-xs text-ink-500">
                            Calculado sobre {{ $breakdown->breakdown['sample_size'] ?? 0 }} reclamações com oportunidade de resposta nos últimos 12 meses.
                            <a href="{{ route('methodology') }}" class="font-medium underline underline-offset-2">Ver metodologia completa</a>
                        </p>
                    </div>
                </section>
            @endif

            {{-- Reclamações recentes --}}
            <section aria-labelledby="reclamacoes">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 id="reclamacoes" class="text-lg font-semibold">Reclamações recentes</h2>
                    <a href="{{ route('companies.complaints', $company->slug) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                        Ver todas <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>

                @if ($complaints->isEmpty())
                    <x-empty-state
                        title="Ainda não há reclamações publicadas"
                        description="Sê a primeira pessoa a partilhar a tua experiência com esta empresa.">
                        <a href="{{ route('complaints.create', ['empresa' => $company->name]) }}" class="btn btn-primary">Fazer uma reclamação</a>
                    </x-empty-state>
                @else
                    <div class="space-y-4">
                        @foreach ($complaints as $complaint)
                            <x-complaint-card :complaint="$complaint" :show-company="false" />
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- Barra lateral --}}
        <aside class="mt-8 lg:mt-0">
            <div class="lg:sticky lg:top-24 space-y-6">
                @if ($stageCounts)
                    <div class="card">
                        <div class="card-body">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-500">Desfecho das reclamações</h2>
                            <ul class="mt-4 space-y-2.5 text-sm">
                                @foreach ($stageCounts as $stage => $count)
                                    @php $enum = ComplaintStage::tryFrom($stage); @endphp
                                    @if ($enum)
                                        <li class="flex items-center justify-between gap-3">
                                            <a href="{{ route('companies.complaints', ['company' => $company->slug, 'estado' => $stage]) }}"
                                               class="text-ink-600 hover:text-brand-700">{{ $enum->label() }}</a>
                                            <span class="font-semibold text-ink-900">{{ $count }}</span>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @unless ($company->isClaimed())
                    <div class="card">
                        <div class="card-body">
                            <h2 class="text-sm font-semibold">É desta empresa?</h2>
                            <p class="mt-1.5 text-sm text-ink-600">
                                Reivindica esta ficha gratuitamente para responderes às reclamações e acompanhares os teus indicadores.
                            </p>
                            <a href="{{ route('register.business', ['empresa' => $company->name]) }}" class="btn btn-secondary mt-4 w-full">
                                Reivindicar ficha
                            </a>
                        </div>
                    </div>
                @endunless

                <div class="card">
                    <div class="card-body">
                        <h2 class="text-sm font-semibold">Nota importante</h2>
                        <p class="mt-1.5 text-xs leading-relaxed text-ink-500">
                            Estes indicadores medem o comportamento da empresa perante reclamações publicadas no queixa.me.
                            Não são uma avaliação da qualidade dos seus produtos ou serviços, nem uma recomendação de compra.
                        </p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
