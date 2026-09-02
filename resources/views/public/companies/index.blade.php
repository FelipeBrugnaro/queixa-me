@extends('layouts.app')

@section('content')
<div class="container-page py-8">

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl font-bold sm:text-4xl">Diretório de empresas</h1>
        <p class="mt-3 text-ink-600">
            Consulta o historial de reclamações, a taxa de resposta e o índice de satisfação
            antes de comprares ou contratares.
        </p>
    </header>

    <form method="GET" class="card mb-8">
        <div class="card-body flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="q" class="label">Procurar empresa</label>
                <input id="q" name="q" type="search" value="{{ $term }}" placeholder="nome da empresa ou marca" class="input">
            </div>
            <div class="sm:w-56">
                <label for="ordenar" class="label">Ordenar por</label>
                <select id="ordenar" name="ordenar" class="input">
                    <option value="reclamacoes" @selected($order === 'reclamacoes')>Mais reclamações</option>
                    <option value="indice" @selected($order === 'indice')>Melhor índice</option>
                    <option value="nome" @selected($order === 'nome')>Nome (A–Z)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary sm:w-auto">Procurar</button>
        </div>
    </form>

    @if ($categories->isNotEmpty() && $term === '')
        <nav aria-label="Setores" class="mb-8">
            <ul class="flex flex-wrap gap-2">
                @foreach ($categories as $category)
                    <li>
                        <a href="{{ route('companies.category', $category) }}"
                           class="badge bg-white text-ink-700 ring-ink-200 transition hover:bg-ink-50 hover:ring-ink-300">
                            {{ $category->name }}
                            <span class="text-ink-400">{{ $category->companies_count }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if ($companies->isEmpty())
        <x-empty-state
            title="Não encontrámos essa empresa"
            description="Podes indicá-la ao criares a tua reclamação — nós validamos a ficha depois.">
            <a href="{{ route('complaints.create', ['empresa' => $term]) }}" class="btn btn-primary">Reclamar sobre "{{ $term }}"</a>
        </x-empty-state>
    @else
        <p class="mb-4 text-sm text-ink-500">
            <strong class="font-semibold text-ink-800">{{ number_format($companies->total(), 0, ',', ' ') }}</strong>
            {{ $companies->total() === 1 ? 'empresa' : 'empresas' }}
        </p>

        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($companies as $company)
                <x-company-card :company="$company" />
            @endforeach
        </div>

        {{ $companies->links() }}
    @endif
</div>
@endsection
