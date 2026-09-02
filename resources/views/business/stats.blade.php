@extends('layouts.panel')

@php
    use App\Domain\Complaints\Enums\ComplaintStage;
    use App\Http\Controllers\PublicSite\CompareController;

    $maxIndex = max(1, (int) ($monthly->max('satisfaction_index') ?? 1));
    $componentLabels = [
        'response_rate' => 'Responder às reclamações',
        'resolution_rate' => 'Resolver, confirmado pelo consumidor',
        'satisfaction' => 'Avaliação dos consumidores',
        'speed' => 'Rapidez da primeira resposta',
    ];
@endphp

@section('panel-heading')
    <h1 class="text-2xl font-bold">Estatísticas</h1>
    <p class="mt-1 text-sm text-ink-600">
        Os mesmos números que aparecem publicamente, com a composição exata do índice.
    </p>
@endsection

@section('panel')
<div class="space-y-6">

    {{-- Indicadores atuais --}}
    <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Índice de satisfação', $company->satisfaction_index !== null ? number_format($company->satisfaction_index, 0) : '—'],
            ['Taxa de resposta', $company->response_rate !== null ? number_format($company->response_rate, 0).'%' : '—'],
            ['Taxa de resolução', $company->resolution_rate !== null ? number_format($company->resolution_rate, 0).'%' : '—'],
            ['Tempo médio de resposta', $company->avg_first_response_minutes !== null ? CompareController::humanDuration($company->avg_first_response_minutes) : '—'],
        ] as [$label, $value])
            <div class="card">
                <div class="p-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-500">{{ $label }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-ink-900">{{ $value }}</dd>
                </div>
            </div>
        @endforeach
    </dl>

    {{-- Evolução mensal --}}
    @if ($monthly->isNotEmpty())
        <section class="card" aria-labelledby="evolucao">
            <div class="card-body">
                <h2 id="evolucao" class="text-lg font-semibold">Evolução do índice</h2>
                <p class="mt-1 text-sm text-ink-500">Últimos {{ $monthly->count() }} meses.</p>

                <div class="mt-6 flex items-end gap-1.5 sm:gap-2" role="img"
                     aria-label="Evolução mensal do índice de satisfação">
                    @foreach ($monthly as $stat)
                        @php $height = $stat->satisfaction_index ? max(6, (int) round($stat->satisfaction_index / $maxIndex * 120)) : 3; @endphp
                        <div class="flex flex-1 flex-col items-center gap-1.5">
                            <span class="text-[10px] font-medium text-ink-500">
                                {{ $stat->satisfaction_index !== null ? number_format($stat->satisfaction_index, 0) : '—' }}
                            </span>
                            <div class="w-full rounded-t bg-brand-500/80" style="height: {{ $height }}px"
                                 title="{{ $stat->period_start->translatedFormat('F Y') }}"></div>
                            <span class="text-[10px] text-ink-400">{{ $stat->period_start->translatedFormat('M') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Composição do índice --}}
    @if ($rolling && $rolling->breakdown)
        <section class="card" aria-labelledby="composicao">
            <div class="card-body">
                <h2 id="composicao" class="text-lg font-semibold">Composição do índice</h2>
                <p class="mt-1 text-sm text-ink-500">
                    Índice bruto {{ $rolling->raw_index !== null ? number_format($rolling->raw_index, 1) : '—' }} ·
                    índice final {{ $rolling->satisfaction_index !== null ? number_format($rolling->satisfaction_index, 1) : '—' }}
                    (após correção estatística sobre {{ $rolling->breakdown['sample_size'] ?? 0 }} reclamações).
                </p>

                <dl class="mt-5 space-y-4">
                    @foreach (($rolling->breakdown['components'] ?? []) as $key => $value)
                        <div>
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <dt class="text-ink-700">
                                    {{ $componentLabels[$key] ?? $key }}
                                    <span class="text-xs text-ink-400">({{ number_format(($weights[$key] ?? 0) * 100, 0) }}% do peso)</span>
                                </dt>
                                <dd class="font-semibold text-ink-900">{{ $value !== null ? number_format($value, 0) : 'sem dados' }}</dd>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ink-100">
                                <div class="h-full rounded-full {{ ($value ?? 0) >= 70 ? 'bg-emerald-500' : (($value ?? 0) >= 45 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                     style="width: {{ $value !== null ? min(100, (int) $value) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </dl>

                <p class="mt-5 rounded-xl bg-ink-100 px-4 py-3 text-xs leading-relaxed text-ink-600">
                    A componente mais fraca é a que mais rapidamente faz subir o índice se for
                    corrigida. <a href="{{ route('methodology') }}" class="font-medium underline underline-offset-2">Ver metodologia completa</a>.
                </p>
            </div>
        </section>
    @else
        <x-empty-state
            title="Ainda não há dados suficientes"
            description="Os indicadores são calculados quando existirem reclamações publicadas em número suficiente." />
    @endif

    {{-- Desfecho --}}
    @if ($stageCounts->isNotEmpty())
        <section class="card" aria-labelledby="desfecho">
            <div class="card-body">
                <h2 id="desfecho" class="text-lg font-semibold">Desfecho das reclamações</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach ($stageCounts as $stage => $count)
                        @php $enum = ComplaintStage::tryFrom((string) $stage); @endphp
                        @if ($enum)
                            <li class="flex items-center justify-between gap-3">
                                <span class="badge {{ $enum->badgeClasses() }}">{{ $enum->label() }}</span>
                                <span class="font-semibold text-ink-900">{{ $count }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</div>
@endsection
