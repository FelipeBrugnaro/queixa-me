@extends('layouts.app')

@php
    use App\Http\Controllers\PublicSite\CompareController;

    /*
     * O pódio só aparece na primeira página e sem filtros de ordenação
     * alternativos: "o 1.º lugar" tem de significar sempre a mesma coisa,
     * senão a medalha deixa de valer nada.
     */
    $showPodium = $companies->currentPage() === 1 && $sortKey === 'indice';
    $podium = $showPodium ? $companies->take(3) : collect();
    $rest = $showPodium ? $companies->slice(3) : $companies->getCollection();

    $medals = [
        1 => ['medal-gold', 'Ouro'],
        2 => ['medal-silver', 'Prata'],
        3 => ['medal-bronze', 'Bronze'],
    ];
@endphp

@section('content')

{{-- Cabeçalho --}}
<section class="relative overflow-hidden border-b border-ink-200"
         style="background: linear-gradient(160deg, var(--color-brand-800), var(--color-brand-950) 70%)">
    <div class="container-page relative py-14">
        <div class="max-w-3xl">
            <p class="eyebrow text-brand-300">Classificação</p>
            <h1 class="mt-4 text-4xl text-white sm:text-5xl">
                Ranking de empresas
                @if ($activeCategory)
                    <span class="text-brand-300">· {{ $activeCategory->name }}</span>
                @endif
            </h1>
            <p class="mt-5 text-[0.9375rem] leading-relaxed text-brand-100/80">
                Ordenado por comportamento, não por volume. Uma empresa grande recebe naturalmente
                mais reclamações do que uma pequena — o que medimos é se responde, se resolve, em
                quanto tempo e com que satisfação de quem reclamou.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-semibold text-brand-200/90">
                <span class="inline-flex items-center gap-1.5">
                    <span class="size-1.5 rounded-full bg-brand-400"></span> Janela de 12 meses
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="size-1.5 rounded-full bg-brand-400"></span> Mínimo de {{ $minimum }} reclamações
                </span>
                <a href="{{ route('methodology') }}" class="underline decoration-brand-400 underline-offset-2 transition hover:text-white">
                    Ver metodologia
                </a>
            </div>
        </div>
    </div>
</section>

<div class="container-page py-10">

    {{-- Filtros --}}
    <form method="GET" class="card mb-10">
        <div class="card-body grid gap-4 sm:grid-cols-3">
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

        {{-- Pódio --}}
        @if ($podium->isNotEmpty())
            <section class="mb-12" aria-labelledby="podio">
                <h2 id="podio" class="sr-only">Pódio</h2>

                {{-- Ordem visual 2.º / 1.º / 3.º em ecrã largo, como num pódio
                     a sério; em ecrã pequeno volta à ordem de classificação. --}}
                <ol class="grid items-end gap-5 sm:grid-cols-3">
                    @foreach ($podium as $index => $company)
                        @php
                            $position = $index + 1;
                            [$medalClass, $medalName] = $medals[$position];
                            $order = [1 => 'sm:order-2', 2 => 'sm:order-1', 3 => 'sm:order-3'][$position];
                            $lift = $position === 1 ? 'sm:pb-6' : '';
                        @endphp

                        <li class="{{ $order }} {{ $lift }}">
                            <article class="card card-hover group relative overflow-hidden text-center
                                            {{ $position === 1 ? 'ring-2 ring-gold-300' : '' }}">

                                {{-- Faixa superior com a cor da medalha --}}
                                <div class="h-1.5 {{ $medalClass }}"></div>

                                <div class="px-5 pb-6 pt-7">
                                    <div class="relative mx-auto w-fit">
                                        <x-company-avatar :company="$company" size="xl" />

                                        <span class="medal {{ $medalClass }} absolute -bottom-2 -right-2 size-8 text-sm ring-4 ring-surface"
                                              aria-hidden="true">
                                            {{ $position }}
                                        </span>
                                    </div>

                                    <p class="mt-5 text-[0.6875rem] font-extrabold uppercase tracking-[0.1em]
                                              {{ $position === 1 ? 'text-gold-700' : ($position === 2 ? 'text-silver-700' : 'text-bronze-700') }}">
                                        {{ $medalName }}
                                    </p>

                                    <h3 class="mt-1.5 text-lg leading-snug">
                                        <a href="{{ $company->url() }}" class="transition hover:text-brand-700">
                                            <span class="absolute inset-0" aria-hidden="true"></span>
                                            {{ $company->name }}
                                        </a>
                                    </h3>

                                    <p class="mt-1 truncate text-xs text-ink-500">{{ $company->category?->name }}</p>

                                    <p class="font-display mt-5 text-5xl leading-none text-ink-900">
                                        {{ number_format($company->satisfaction_index, 0) }}
                                        <span class="text-lg font-bold text-ink-300">/100</span>
                                    </p>

                                    <div class="index-track mt-3">
                                        <div class="index-fill {{ $company->satisfactionBarClass() }}"
                                             style="width: {{ max(2, min(100, (int) round($company->satisfaction_index))) }}%"></div>
                                    </div>

                                    <dl class="mt-5 grid grid-cols-3 gap-2 border-t border-ink-100 pt-4">
                                        @foreach ([
                                            ['Resposta', $company->response_rate !== null ? number_format($company->response_rate, 0).'%' : '—'],
                                            ['Resolução', $company->resolution_rate !== null ? number_format($company->resolution_rate, 0).'%' : '—'],
                                            ['Casos', number_format($company->published_complaints_count, 0, ',', ' ')],
                                        ] as [$label, $value])
                                            <div>
                                                <dt class="text-[0.625rem] font-bold uppercase tracking-wide text-ink-400">{{ $label }}</dt>
                                                <dd class="mt-0.5 text-sm font-extrabold text-ink-900">{{ $value }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        {{-- Restantes classificados --}}
        @if ($rest->isNotEmpty())
            <section aria-labelledby="classificacao">
                <h2 id="classificacao" class="mb-5 text-xl">
                    {{ $showPodium ? 'Restante classificação' : 'Classificação' }}
                </h2>

                {{-- Tabela em ecrã largo --}}
                <div class="card hidden overflow-hidden md:block">
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <caption class="sr-only">Ranking de empresas por {{ $sorts[$sortKey]['label'] }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="w-16">Pos.</th>
                                    <th scope="col">Empresa</th>
                                    <th scope="col" class="text-right">Índice</th>
                                    <th scope="col" class="text-right">Resposta</th>
                                    <th scope="col" class="text-right">Resolução</th>
                                    <th scope="col" class="text-right">Tempo médio</th>
                                    <th scope="col" class="text-right">Avaliação</th>
                                    <th scope="col" class="text-right">Casos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rest as $index => $company)
                                    @php $position = $companies->firstItem() + ($showPodium ? $index + 3 : $index); @endphp
                                    <tr>
                                        <td>
                                            <span class="medal medal-plain size-8 text-xs">{{ $position }}</span>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <x-company-avatar :company="$company" size="sm" />
                                                <div class="min-w-0">
                                                    <a href="{{ $company->url() }}" class="font-bold text-ink-900 transition hover:text-brand-700">{{ $company->name }}</a>
                                                    <p class="truncate text-xs text-ink-500">{{ $company->category?->name }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <span class="font-display text-xl leading-none text-ink-900">
                                                {{ number_format($company->satisfaction_index, 0) }}
                                            </span>
                                            <span class="index-track mt-2 ml-auto block w-20">
                                                <span class="index-fill {{ $company->satisfactionBarClass() }}"
                                                      style="width: {{ max(2, min(100, (int) round($company->satisfaction_index))) }}%"></span>
                                            </span>
                                        </td>
                                        <td class="text-right font-semibold">{{ $company->response_rate !== null ? number_format($company->response_rate, 0).'%' : '—' }}</td>
                                        <td class="text-right font-semibold">{{ $company->resolution_rate !== null ? number_format($company->resolution_rate, 0).'%' : '—' }}</td>
                                        <td class="text-right text-ink-600">{{ $company->avg_first_response_minutes !== null ? CompareController::humanDuration($company->avg_first_response_minutes) : '—' }}</td>
                                        <td class="text-right text-ink-600">{{ $company->average_rating !== null ? number_format($company->average_rating, 1, ',', '') : '—' }}</td>
                                        <td class="text-right text-ink-400">{{ number_format($company->published_complaints_count, 0, ',', ' ') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Cartões em ecrã pequeno --}}
                <div class="grid gap-4 md:hidden">
                    @foreach ($rest as $index => $company)
                        <x-company-card :company="$company"
                                        :rank="$companies->firstItem() + ($showPodium ? $index + 3 : $index)" />
                    @endforeach
                </div>
            </section>
        @endif

        {{ $companies->links() }}
    @endif

    <p class="mt-10 rounded-2xl bg-ink-100 px-5 py-4 text-xs leading-relaxed text-ink-600">
        <strong class="font-bold text-ink-800">Como ler este ranking.</strong>
        O índice resulta de quatro componentes ponderadas e é suavizado estatisticamente, de modo a
        que uma empresa com poucas reclamações não apareça artificialmente no topo nem no fundo.
        Não é uma avaliação da qualidade dos produtos ou serviços das empresas listadas.
    </p>
</div>
@endsection
