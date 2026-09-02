@extends('layouts.app')

@php use App\Http\Controllers\PublicSite\CompareController; @endphp

@section('content')
<div class="container-page py-8">

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl font-bold sm:text-4xl">
            Ranking de empresas
            @if ($activeCategory)
                — {{ $activeCategory->name }}
            @endif
        </h1>
        <p class="mt-3 text-ink-600">
            Ordenado por comportamento, não por volume. Uma empresa grande recebe naturalmente
            mais reclamações do que uma pequena — o que medimos é se responde, se resolve,
            em quanto tempo e com que satisfação de quem reclamou.
        </p>
        <p class="mt-2 text-sm text-ink-500">
            Janela de 12 meses · Mínimo de {{ $minimum }} reclamações para figurar no ranking ·
            <a href="{{ route('methodology') }}" class="font-medium text-brand-700 underline underline-offset-2">Ver metodologia</a>
        </p>
    </header>

    {{-- Filtros --}}
    <form method="GET" class="card mb-8">
        <div class="card-body grid gap-3 sm:grid-cols-3">
            <div>
                <label for="ordenar" class="label">Ordenar por</label>
                <select id="ordenar" name="ordenar" class="input">
                    @foreach ($sorts as $key => $sort)
                        <option value="{{ $key }}" @selected($sortKey === $key)>{{ $sort['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="categoria" class="label">Setor</label>
                <select id="categoria" name="categoria" class="input">
                    <option value="">Todos os setores</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected($activeCategory?->slug === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">Aplicar</button>
            </div>
        </div>
    </form>

    @if ($companies->isEmpty())
        <x-empty-state
            title="Ainda não há empresas com dados suficientes"
            description="Assim que houver reclamações publicadas em número suficiente, o ranking aparece aqui." />
    @else
        {{-- Tabela (desktop) --}}
        <div class="card hidden overflow-hidden md:block">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <caption class="sr-only">Ranking de empresas por {{ $sorts[$sortKey]['label'] }}</caption>
                    <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold">#</th>
                            <th scope="col" class="px-5 py-3 font-semibold">Empresa</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Índice</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Resposta</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Resolução</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Tempo médio</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Avaliação</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Reclamações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($companies as $index => $company)
                            @php $position = $companies->firstItem() + $index; @endphp
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-5 py-4 font-semibold text-ink-400">{{ $position }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <x-company-avatar :company="$company" size="sm" />
                                        <div class="min-w-0">
                                            <a href="{{ $company->url() }}" class="font-semibold text-ink-900 hover:text-brand-700">{{ $company->name }}</a>
                                            <p class="truncate text-xs text-ink-500">{{ $company->category?->name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="badge {{ $company->satisfactionColorClasses() }}">
                                        {{ number_format($company->satisfaction_index, 0) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right font-medium">{{ $company->response_rate !== null ? number_format($company->response_rate, 0).'%' : '—' }}</td>
                                <td class="px-5 py-4 text-right font-medium">{{ $company->resolution_rate !== null ? number_format($company->resolution_rate, 0).'%' : '—' }}</td>
                                <td class="px-5 py-4 text-right text-ink-600">{{ $company->avg_first_response_minutes !== null ? CompareController::humanDuration($company->avg_first_response_minutes) : '—' }}</td>
                                <td class="px-5 py-4 text-right text-ink-600">{{ $company->average_rating !== null ? number_format($company->average_rating, 1, ',', '') : '—' }}</td>
                                <td class="px-5 py-4 text-right text-ink-500">{{ number_format($company->published_complaints_count, 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cartões (móvel) --}}
        <div class="grid gap-4 md:hidden">
            @foreach ($companies as $index => $company)
                <x-company-card :company="$company" :rank="$companies->firstItem() + $index" />
            @endforeach
        </div>

        {{ $companies->links() }}
    @endif

    <p class="mt-8 rounded-xl bg-ink-100 px-4 py-3 text-xs leading-relaxed text-ink-600">
        <strong class="font-semibold text-ink-800">Como ler este ranking.</strong>
        O índice resulta de quatro componentes ponderadas e é suavizado estatisticamente, de modo a que
        uma empresa com poucas reclamações não apareça artificialmente no topo nem no fundo. Não é uma
        avaliação da qualidade dos produtos ou serviços das empresas listadas.
    </p>
</div>
@endsection
