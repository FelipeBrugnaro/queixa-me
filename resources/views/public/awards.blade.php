@extends('layouts.app')

@php use App\Domain\Ratings\Enums\AwardType; @endphp

@section('content')
<div class="container-page py-8">

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl font-bold sm:text-4xl">Marcas do mês</h1>
        <p class="mt-3 text-ink-600">
            As empresas que mais se destacaram a tratar quem reclamou. As distinções são calculadas
            a partir dos mesmos indicadores públicos do ranking, com um mínimo de
            {{ config('queixame.awards.minimum_complaints') }} reclamações no mês — sem esse limite,
            venceria sempre quem tem uma única reclamação respondida.
        </p>
    </header>

    @if ($availablePeriods->isNotEmpty())
        <nav aria-label="Escolher mês" class="mb-8 flex flex-wrap gap-2">
            @foreach ($availablePeriods as $available)
                @php $date = \Illuminate\Support\Carbon::parse($available); @endphp
                <a href="{{ route('awards.period', $date->format('Y-m')) }}"
                   class="badge {{ $date->isSameMonth($period) ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-ink-700 ring-ink-200 hover:bg-ink-50' }}">
                    {{ $date->translatedFormat('F Y') }}
                </a>
            @endforeach
        </nav>
    @endif

    <h2 class="mb-6 text-lg font-semibold text-ink-700">{{ ucfirst($periodLabel) }}</h2>

    @if ($awards->isEmpty())
        <x-empty-state
            title="Ainda não há distinções para este mês"
            description="As Marcas do Mês são apuradas depois de o mês fechar e de haver dados suficientes." />
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($awards as $type => $group)
                @php
                    $award = $group->first();
                    $awardType = AwardType::tryFrom($type);
                    $isMain = $awardType === AwardType::BrandOfTheMonth;
                @endphp

                <article class="card card-hover overflow-hidden {{ $isMain ? 'sm:col-span-2 lg:col-span-1 ring-brand-300' : '' }}">
                    <div class="{{ $isMain ? 'bg-linear-to-br from-brand-600 to-brand-800' : 'bg-ink-800' }} px-5 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-white/90">
                            {{ $awardType?->label() }}
                        </p>
                    </div>

                    <div class="card-body">
                        <div class="flex items-center gap-4">
                            <x-company-avatar :company="$award->company" size="lg" />
                            <div class="min-w-0">
                                <h3 class="truncate text-lg font-semibold">
                                    <a href="{{ $award->company->url() }}" class="hover:text-brand-700">{{ $award->company->name }}</a>
                                </h3>
                                @if ($award->metric_value !== null)
                                    <p class="text-sm font-medium text-ink-600">
                                        @if ($awardType === AwardType::BestService)
                                            {{ number_format($award->metric_value, 1, ',', '') }} h até responder
                                        @elseif ($awardType === AwardType::BestSatisfaction)
                                            {{ number_format($award->metric_value, 1, ',', '') }} / 5
                                        @elseif ($awardType === AwardType::BestImprovement)
                                            +{{ number_format($award->metric_value, 1, ',', '') }} pontos
                                        @else
                                            {{ number_format($award->metric_value, 0) }}{{ $awardType === AwardType::BrandOfTheMonth ? ' / 100' : '%' }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>

                        <p class="mt-4 text-sm leading-relaxed text-ink-600">
                            {{ $award->editorial_note ?: $awardType?->description() }}
                        </p>

                        @if ($award->is_editorial)
                            <p class="mt-3 text-xs font-medium text-amber-700">Distinção atribuída pela equipa editorial.</p>
                        @endif

                        <dl class="mt-5 grid grid-cols-3 gap-3 border-t border-ink-100 pt-4 text-center text-xs">
                            <div>
                                <dt class="text-ink-400">Índice</dt>
                                <dd class="mt-0.5 font-semibold text-ink-900">{{ $award->company->satisfaction_index !== null ? number_format($award->company->satisfaction_index, 0) : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-400">Resposta</dt>
                                <dd class="mt-0.5 font-semibold text-ink-900">{{ $award->company->response_rate !== null ? number_format($award->company->response_rate, 0).'%' : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-400">Resolução</dt>
                                <dd class="mt-0.5 font-semibold text-ink-900">{{ $award->company->resolution_rate !== null ? number_format($award->company->resolution_rate, 0).'%' : '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <p class="mt-8 rounded-xl bg-ink-100 px-4 py-3 text-xs leading-relaxed text-ink-600">
        As distinções não são compradas nem patrocinadas. Resultam da aplicação automática dos
        critérios publicados na <a href="{{ route('methodology') }}" class="font-medium underline underline-offset-2">página de metodologia</a>.
    </p>
</div>
@endsection
