@extends('layouts.app')

@php
    use App\Domain\Complaints\Enums\ComplaintStage;
    use App\Http\Controllers\PublicSite\CompareController;

    $maxHistory = max(1, (int) ($history->max('satisfaction_index') ?? 1));
    $componentLabels = [
        'response_rate' => 'Responde às reclamações',
        'resolution_rate' => 'Resolve, confirmado pelo consumidor',
        'satisfaction' => 'Avaliação dos consumidores',
        'speed' => 'Rapidez da primeira resposta',
    ];
@endphp

@section('content')

{{-- Cabeçalho da ficha --}}
<section class="border-b border-ink-200 bg-surface">
    <div class="container-page py-10">
        <div class="flex flex-wrap items-start gap-x-8 gap-y-6">

            <div class="flex min-w-0 flex-1 items-start gap-5">
                <x-company-avatar :company="$company" size="xl" />

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-3xl sm:text-4xl">{{ $company->name }}</h1>
                        @if ($company->verified_at)
                            <span class="badge bg-brand-50 text-brand-800 ring-brand-200"
                                  title="Ficha reivindicada e validada pela empresa">
                                <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.7 6.3a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4 0l-3-3a1 1 0 1 1 1.4-1.4L9 12.6l6.3-6.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/>
                                </svg>
                                Verificada
                            </span>
                        @endif
                    </div>

                    <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-ink-500">
                        @if ($company->category)
                            <a href="{{ route('companies.category', $company->category) }}" class="transition hover:text-brand-700">
                                {{ $company->category->name }}
                            </a>
                        @endif
                        @if ($company->district)
                            <span class="text-ink-300" aria-hidden="true">/</span> {{ $company->district }}
                        @endif
                        @if ($company->website)
                            <span class="text-ink-300" aria-hidden="true">/</span>
                            <a href="{{ $company->website }}" rel="nofollow noopener" target="_blank" class="transition hover:text-brand-700">Website</a>
                        @endif
                    </p>

                    @if ($company->description)
                        <p class="mt-4 max-w-2xl text-[0.9375rem] leading-relaxed text-ink-600">{{ $company->description }}</p>
                    @endif
                </div>
            </div>

            {{-- Índice em destaque: é o dado que o visitante veio ver --}}
            <div class="w-full max-w-xs shrink-0 rounded-xl border border-ink-200 bg-paper p-5">
                <x-index-badge :company="$company" size="lg" />

                <div class="mt-5 flex gap-2">
                    <a href="{{ route('complaints.create', ['empresa' => $company->name]) }}" class="btn btn-primary flex-1">
                        Reclamar
                    </a>
                    <a href="{{ route('compare.show', ['empresas' => $company->slug]) }}" class="btn btn-secondary">
                        Comparar
                    </a>
                </div>
            </div>
        </div>

        @unless ($company->hasEnoughDataForIndex())
            <p class="mt-6 border-l-2 border-ink-300 py-2 pl-4 text-sm text-ink-600">
                Esta empresa ainda tem poucas reclamações publicadas para um índice estatisticamente
                fiável. Mostramos os números em bruto, mas não a colocamos no ranking —
                <a href="{{ route('methodology') }}" class="underline decoration-ink-300 underline-offset-2 hover:text-ink-900">porquê?</a>
            </p>
        @endunless
    </div>
</section>

{{-- Indicadores --}}
<section class="border-b border-ink-200 bg-surface" aria-labelledby="indicadores">
    <div class="container-page">
        <h2 id="indicadores" class="sr-only">Indicadores</h2>
        <dl class="grid grid-cols-2 divide-ink-200 sm:grid-cols-4 sm:divide-x">
            @foreach ([
                ['Reclamações publicadas', number_format($company->published_complaints_count, 0, ',', ' '), 'Total no portal'],
                ['Taxa de resposta', $company->response_rate !== null ? number_format($company->response_rate, 0, ',', '').'%' : '—', 'Reclamações respondidas'],
                ['Taxa de resolução', $company->resolution_rate !== null ? number_format($company->resolution_rate, 0, ',', '').'%' : '—', 'Confirmada pelo consumidor'],
                ['Tempo médio de resposta', $company->avg_first_response_minutes !== null ? CompareController::humanDuration($company->avg_first_response_minutes) : '—', 'Até à primeira resposta'],
            ] as $i => [$label, $value, $hint])
                <div class="px-0 py-6 {{ $i > 0 ? 'sm:pl-6' : '' }} {{ $i < 3 ? 'sm:pr-6' : '' }}">
                    <dt class="text-xs text-ink-500">{{ $label }}</dt>
                    <dd class="font-display mt-1.5 text-3xl leading-none text-ink-900">{{ $value }}</dd>
                    <p class="mt-1.5 text-[0.6875rem] text-ink-400">{{ $hint }}</p>
                </div>
            @endforeach
        </dl>
    </div>
</section>

<div class="container-page py-12">
    <div class="lg:grid lg:grid-cols-12 lg:gap-12">
        <div class="min-w-0 space-y-14 lg:col-span-8">

            {{-- Evolução --}}
            @if ($history->isNotEmpty())
                <section aria-labelledby="evolucao">
                    <div class="rule-heading">
                        <h2 id="evolucao" class="text-2xl">Evolução do índice</h2>
                        <span class="text-sm text-ink-400">últimos {{ $history->count() }} meses</span>
                    </div>

                    {{-- Gráfico em HTML puro: sem biblioteca externa, sem
                         JavaScript, e legível por leitores de ecrã. --}}
                    <div class="mt-8 flex h-40 items-end gap-1.5 sm:gap-2" role="img"
                         aria-label="Evolução mensal do índice de satisfação de {{ $company->name }}">
                        @foreach ($history as $stat)
                            @php $height = $stat->satisfaction_index ? max(3, (int) round($stat->satisfaction_index / $maxHistory * 100)) : 1; @endphp
                            <div class="flex flex-1 flex-col items-center justify-end gap-2">
                                <span class="text-[0.625rem] tabular-nums text-ink-500">
                                    {{ $stat->satisfaction_index !== null ? number_format($stat->satisfaction_index, 0) : '' }}
                                </span>
                                <div class="w-full rounded-t-sm bg-brand-600/85 transition hover:bg-brand-700"
                                     style="height: {{ $height }}%"
                                     title="{{ $stat->period_start->translatedFormat('F Y') }}: {{ $stat->satisfaction_index ?? 'sem dados' }}"></div>
                                <span class="text-[0.625rem] uppercase text-ink-400">{{ $stat->period_start->translatedFormat('M') }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Composição do índice --}}
            @if ($breakdown && $breakdown->breakdown)
                <section aria-labelledby="composicao">
                    <div class="rule-heading">
                        <h2 id="composicao" class="text-2xl">Como se compõe este índice</h2>
                    </div>

                    <p class="mt-4 text-sm text-ink-500">
                        Índice bruto <strong class="font-semibold text-ink-800">{{ $breakdown->raw_index !== null ? number_format($breakdown->raw_index, 1, ',', '') : '—' }}</strong>
                        <span class="text-ink-300" aria-hidden="true">/</span>
                        índice final <strong class="font-semibold text-ink-800">{{ $breakdown->satisfaction_index !== null ? number_format($breakdown->satisfaction_index, 1, ',', '') : '—' }}</strong>
                        após correção estatística.
                    </p>

                    <dl class="mt-6 space-y-5">
                        @foreach (($breakdown->breakdown['components'] ?? []) as $key => $value)
                            @php $weight = ($breakdown->breakdown['weights'][$key] ?? 0) * 100; @endphp
                            <div>
                                <div class="flex items-baseline justify-between gap-3 text-sm">
                                    <dt class="text-ink-700">
                                        {{ $componentLabels[$key] ?? $key }}
                                        <span class="ml-1 text-xs text-ink-400">{{ number_format($weight, 0) }}% do peso</span>
                                    </dt>
                                    <dd class="font-semibold tabular-nums text-ink-900">
                                        {{ $value !== null ? number_format($value, 0) : '—' }}
                                    </dd>
                                </div>
                                <div class="index-track mt-2">
                                    <div class="index-fill bg-brand-600" style="width: {{ $value !== null ? min(100, (int) $value) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </dl>

                    <p class="mt-6 text-xs leading-relaxed text-ink-500">
                        Calculado sobre {{ $breakdown->breakdown['sample_size'] ?? 0 }} reclamações com
                        oportunidade de resposta nos últimos 12 meses.
                        <a href="{{ route('methodology') }}" class="underline decoration-ink-300 underline-offset-2 hover:text-ink-800">
                            Ver metodologia completa
                        </a>
                    </p>
                </section>
            @endif

            {{-- Reclamações --}}
            <section aria-labelledby="reclamacoes">
                <div class="rule-heading">
                    <h2 id="reclamacoes" class="text-2xl">Reclamações recentes</h2>
                    <a href="{{ route('companies.complaints', $company->slug) }}"
                       class="ml-auto pb-1 text-sm font-semibold text-brand-700 transition hover:text-brand-900">
                        Ver todas <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>

                <div class="mt-6">
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
                </div>
            </section>
        </div>

        {{-- Coluna lateral --}}
        <aside class="mt-12 lg:col-span-4 lg:mt-0">
            <div class="space-y-8 lg:sticky lg:top-28">

                @if ($stageCounts)
                    <div>
                        <h2 class="eyebrow border-b border-ink-200 pb-3">Desfecho das reclamações</h2>
                        <ul class="text-sm">
                            @foreach ($stageCounts as $stage => $count)
                                @php $enum = ComplaintStage::tryFrom($stage); @endphp
                                @if ($enum)
                                    <li class="border-b border-ink-100">
                                        <a href="{{ route('companies.complaints', ['company' => $company->slug, 'estado' => $stage]) }}"
                                           class="group flex items-center justify-between gap-3 py-3">
                                            <span class="text-ink-600 transition group-hover:text-brand-700">{{ $enum->label() }}</span>
                                            <span class="font-semibold tabular-nums text-ink-900">{{ $count }}</span>
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif

                @unless ($company->isClaimed())
                    <div class="card">
                        <div class="card-body">
                            <h2 class="text-base" style="font-family: var(--font-sans); font-weight: 600">É desta empresa?</h2>
                            <p class="mt-2 text-sm leading-relaxed text-ink-600">
                                Reivindica esta ficha gratuitamente para responderes às reclamações
                                e acompanhares os teus indicadores.
                            </p>
                            <a href="{{ route('register.business', ['empresa' => $company->name]) }}" class="btn btn-secondary mt-4 w-full">
                                Reivindicar ficha
                            </a>
                        </div>
                    </div>
                @endunless

                <p class="border-l-2 border-ink-200 pl-4 text-xs leading-relaxed text-ink-500">
                    Estes indicadores medem o comportamento da empresa perante reclamações
                    publicadas no queixa.me. Não são uma avaliação da qualidade dos seus produtos
                    ou serviços, nem uma recomendação de compra.
                </p>
            </div>
        </aside>
    </div>
</div>
@endsection
