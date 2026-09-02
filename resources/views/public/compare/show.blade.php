@extends('layouts.app')

@section('content')
<div class="container-page py-8">

    <header class="mb-8">
        <h1 class="text-3xl font-bold sm:text-4xl">
            {{ $companies->pluck('name')->implode(' vs ') }}
        </h1>
        <p class="mt-3 max-w-3xl text-ink-600">
            Comparação dos indicadores de reclamação dos últimos 12 meses.
            Os valores medem comportamento perante reclamações, não a qualidade dos produtos ou serviços.
        </p>
        <a href="{{ route('compare') }}" class="mt-4 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800">
            <span aria-hidden="true">&larr;</span>&nbsp;Escolher outras empresas
        </a>
    </header>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-sm">
                <caption class="sr-only">Comparação de indicadores entre {{ $companies->pluck('name')->implode(', ') }}</caption>
                <thead>
                    <tr class="border-b border-ink-200">
                        <th scope="col" class="w-56 px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            Indicador
                        </th>
                        @foreach ($companies as $company)
                            <th scope="col" class="px-5 py-4 text-center">
                                <a href="{{ $company->url() }}" class="inline-flex flex-col items-center gap-2 hover:text-brand-700">
                                    <x-company-avatar :company="$company" size="md" />
                                    <span class="text-sm font-semibold text-ink-900">{{ $company->name }}</span>
                                    <span class="text-xs font-normal text-ink-500">{{ $company->category?->name }}</span>
                                </a>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @foreach ($rows as $row)
                        @php
                            // O vencedor é calculado por linha para poder ser
                            // destacado sem repetir a lógica na marcação.
                            $values = $companies->map(fn ($c) => ($row['value'])($c));
                            $comparable = $values->filter(fn ($v) => $v !== null);
                            $best = null;

                            if ($row['higher_is_better'] !== null && $comparable->count() > 1) {
                                $best = $row['higher_is_better'] ? $comparable->max() : $comparable->min();
                            }
                        @endphp
                        <tr>
                            <th scope="row" class="px-5 py-4 text-left align-top">
                                <span class="block font-medium text-ink-800">{{ $row['label'] }}</span>
                                <span class="mt-0.5 block text-xs font-normal text-ink-500">{{ $row['hint'] }}</span>
                            </th>
                            @foreach ($companies as $index => $company)
                                @php
                                    $value = $values[$index];
                                    $isBest = $best !== null && $value !== null && (float) $value === (float) $best;
                                @endphp
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 font-semibold
                                                 {{ $isBest ? 'bg-emerald-50 text-emerald-700' : 'text-ink-800' }}">
                                        {{ ($row['format'])($value) }}
                                        @if ($isBest)
                                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <title>Melhor valor</title>
                                                <path fill-rule="evenodd" d="M16.7 6.3a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4 0l-3-3a1 1 0 1 1 1.4-1.4L9 12.6l6.3-6.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Estado dos dados por empresa --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($companies as $company)
            @php $stat = $stats[$company->id] ?? null; @endphp
            <div class="card">
                <div class="card-body">
                    <h2 class="text-sm font-semibold">{{ $company->name }}</h2>
                    @if ($stat && $stat->is_ranked)
                        <p class="mt-1 text-xs text-ink-500">
                            Baseado em {{ $stat->complaints_count }} reclamações dos últimos 12 meses.
                        </p>
                    @else
                        <p class="mt-1 text-xs text-amber-700">
                            Dados insuficientes para um índice fiável. Interpreta os valores com cautela.
                        </p>
                    @endif
                    <a href="{{ $company->url() }}" class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800">
                        Ver ficha completa <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <p class="mt-8 rounded-xl bg-ink-100 px-4 py-3 text-xs leading-relaxed text-ink-600">
        O número de reclamações é apresentado como informação de contexto, não como critério de mérito:
        depende sobretudo da dimensão da empresa e do número de clientes que serve.
        <a href="{{ route('methodology') }}" class="font-medium underline underline-offset-2">Ver metodologia</a>
    </p>
</div>
@endsection
