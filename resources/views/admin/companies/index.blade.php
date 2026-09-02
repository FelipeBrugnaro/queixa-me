@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Empresas</h1>
    <p class="mt-1 text-sm text-ink-600">
        Fichas por validar, reivindicações e gestão de duplicados.
    </p>
@endsection

@section('panel')

    {{-- Reivindicações pendentes --}}
    @if ($claims->isNotEmpty())
        <section class="mb-8" aria-labelledby="reivindicacoes">
            <h2 id="reivindicacoes" class="mb-3 text-sm font-semibold uppercase tracking-wide text-amber-700">
                Reivindicações por decidir ({{ $claims->count() }})
            </h2>

            <ul class="space-y-3">
                @foreach ($claims as $claim)
                    <li class="card ring-amber-200">
                        <div class="card-body">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="font-medium text-ink-900">{{ $claim->company?->name }}</p>
                                    <p class="mt-0.5 text-sm text-ink-600">
                                        {{ $claim->user?->name }} · {{ $claim->work_email }}
                                    </p>
                                    @if ($claim->company?->website)
                                        <p class="text-xs text-ink-400">Site oficial: {{ $claim->company->website }}</p>
                                    @endif
                                    @if ($claim->evidence)
                                        <p class="mt-2 rounded-lg bg-ink-50 p-3 text-sm text-ink-600">{{ $claim->evidence }}</p>
                                    @endif
                                </div>
                                <span class="shrink-0 text-xs text-ink-400">{{ $claim->created_at?->diffForHumans() }}</span>
                            </div>

                            <form method="POST" action="{{ route('admin.companies.claim.decide', $claim) }}"
                                  class="mt-4 flex flex-wrap items-end gap-3">
                                @csrf
                                <div class="min-w-48 flex-1">
                                    <label for="notes_{{ $claim->id }}" class="label">Nota da decisão</label>
                                    <input id="notes_{{ $claim->id }}" name="notes" type="text" maxlength="1000" class="input">
                                </div>
                                <button type="submit" name="decision" value="approve" class="btn btn-primary">Aprovar</button>
                                <button type="submit" name="decision" value="reject" class="btn btn-secondary">Recusar</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Filtros --}}
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="min-w-48 flex-1">
            <label for="q" class="label">Pesquisar</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="nome da empresa" class="input">
        </div>
        <div class="w-44">
            <label for="estado" class="label">Estado</label>
            <select id="estado" name="estado" class="input">
                <option value="">Todos</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('estado') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
    </form>

    @if ($companies->isEmpty())
        <x-empty-state title="Nenhuma empresa encontrada" />
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <caption class="sr-only">Empresas registadas</caption>
                    <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold">Empresa</th>
                            <th scope="col" class="px-5 py-3 font-semibold">Estado</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Reclamações</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Índice</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($companies as $company)
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-ink-900">{{ $company->name }}</p>
                                    <p class="text-xs text-ink-500">{{ $company->category?->name ?? 'Sem categoria' }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="badge bg-ink-100 text-ink-700 ring-ink-200">{{ $company->status->label() }}</span>
                                </td>
                                <td class="px-5 py-3 text-right text-ink-600">{{ $company->published_complaints_count }}</td>
                                <td class="px-5 py-3 text-right text-ink-600">
                                    {{ $company->satisfaction_index !== null ? number_format($company->satisfaction_index, 0) : '—' }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.companies.edit', $company) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                                        Gerir
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $companies->links() }}
    @endif
@endsection
