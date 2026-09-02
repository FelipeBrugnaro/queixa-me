@extends('layouts.app')

@section('content')
<div class="container-page py-8">

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl font-bold sm:text-4xl">Comparar marcas</h1>
        <p class="mt-3 text-ink-600">
            Escolhe até {{ $max }} empresas e vê lado a lado como cada uma trata quem reclama.
        </p>
    </header>

    {{-- data-compare-target: o app.js converte a seleção em ?empresas=a,b
         para produzir URLs limpos e partilháveis. --}}
    <form action="{{ route('compare.show') }}" method="GET" class="card"
          data-compare-form data-compare-max="{{ $max }}"
          data-compare-target="{{ route('compare.show') }}">
        <div class="card-body">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-semibold">Empresas com mais atividade</h2>
                <span data-compare-counter class="text-sm text-ink-500">0 de {{ $max }} selecionadas</span>
            </div>

            <fieldset>
                <legend class="sr-only">Selecionar empresas a comparar</legend>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($popular as $company)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 ring-1 ring-ink-200 transition hover:bg-ink-50 has-checked:bg-brand-50 has-checked:ring-brand-300">
                            <input type="checkbox" name="empresas[]" value="{{ $company->slug }}" class="checkbox mt-0">
                            <x-company-avatar :company="$company" size="sm" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-ink-900">{{ $company->name }}</span>
                                <span class="block truncate text-xs text-ink-500">
                                    {{ $company->published_complaints_count }} reclamações
                                    @if ($company->satisfaction_index !== null)
                                        · índice {{ number_format($company->satisfaction_index, 0) }}
                                    @endif
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary">Comparar selecionadas</button>
            </div>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        Não encontras a empresa que procuras?
        <a href="{{ route('companies.index') }}" class="font-semibold text-brand-700 hover:text-brand-800">Procura no diretório</a>
    </p>
</div>
@endsection

