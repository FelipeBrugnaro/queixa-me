@extends('layouts.app')

@section('content')
<div class="container-page py-8">

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl font-bold sm:text-4xl">
            @if ($activeCompany)
                Reclamações sobre {{ $activeCompany->name }}
            @else
                Reclamações de consumidores
            @endif
        </h1>
        <p class="mt-3 text-ink-600">
            Todas as reclamações aqui publicadas foram analisadas pela nossa equipa antes de ficarem visíveis.
            A empresa visada é sempre notificada e pode responder.
        </p>
    </header>

    <div class="lg:grid lg:grid-cols-[17rem_1fr] lg:gap-8">

        {{-- Filtros --}}
        <aside class="mb-8 lg:mb-0">
            <form method="GET" action="{{ route('complaints.index') }}" class="lg:sticky lg:top-24">
                <div class="card">
                    <div class="card-body space-y-5">
                        <div>
                            <label for="q" class="label">Pesquisar</label>
                            <input id="q" name="q" type="search" value="{{ $filters['q'] }}"
                                   placeholder="palavra-chave" class="input">
                        </div>

                        <div>
                            <label for="categoria" class="label">Categoria</label>
                            <select id="categoria" name="categoria" class="input">
                                <option value="">Todas</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->slug }}" @selected($filters['categoria'] === $category->slug)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="estado" class="label">Estado</label>
                            <select id="estado" name="estado" class="input">
                                <option value="">Todos</option>
                                @foreach ($stages as $stage)
                                    <option value="{{ $stage->value }}" @selected($filters['estado'] === $stage->value)>
                                        {{ $stage->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="distrito" class="label">Distrito</label>
                            <select id="distrito" name="distrito" class="input">
                                <option value="">Todos</option>
                                @foreach ($districts as $district)
                                    <option value="{{ $district }}" @selected($filters['distrito'] === $district)>{{ $district }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="periodo" class="label">Período</label>
                            <select id="periodo" name="periodo" class="input">
                                <option value="">Sempre</option>
                                @foreach (['7' => 'Últimos 7 dias', '30' => 'Últimos 30 dias', '90' => 'Últimos 3 meses', '365' => 'Último ano'] as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['periodo'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($filters['empresa'])
                            <input type="hidden" name="empresa" value="{{ $filters['empresa'] }}">
                        @endif

                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary flex-1">Filtrar</button>
                            <a href="{{ route('complaints.index') }}" class="btn btn-ghost">Limpar</a>
                        </div>
                    </div>
                </div>
            </form>
        </aside>

        {{-- Resultados --}}
        <div class="min-w-0">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-ink-500">
                    <strong class="font-semibold text-ink-800">{{ number_format($complaints->total(), 0, ',', ' ') }}</strong>
                    {{ $complaints->total() === 1 ? 'reclamação' : 'reclamações' }}
                </p>

                <form method="GET" class="flex items-center gap-2">
                    @foreach ($filters as $key => $value)
                        @if ($value && $key !== 'ordenar')
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="ordenar" class="text-sm text-ink-500">Ordenar</label>
                    <select id="ordenar" name="ordenar" class="input py-1.5 text-sm" onchange="this.form.submit()">
                        @foreach (['recentes' => 'Mais recentes', 'antigas' => 'Mais antigas', 'populares' => 'Mais vistas', 'respondidas' => 'Com resposta recente'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['ordenar'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if ($complaints->isEmpty())
                <x-empty-state
                    title="Não encontrámos reclamações"
                    description="Experimenta alargar o período ou remover alguns filtros.">
                    <a href="{{ route('complaints.index') }}" class="btn btn-secondary">Ver todas as reclamações</a>
                </x-empty-state>
            @else
                <div class="space-y-4">
                    @foreach ($complaints as $complaint)
                        <x-complaint-card :complaint="$complaint" />
                    @endforeach
                </div>

                {{ $complaints->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
