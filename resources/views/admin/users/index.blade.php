@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Utilizadores</h1>
    <p class="mt-1 text-sm text-ink-600">Contas de consumidores, empresas e equipa.</p>
@endsection

@section('panel')

    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="min-w-48 flex-1">
            <label for="q" class="label">Pesquisar</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="nome, email ou nome público" class="input">
        </div>
        <div class="w-40">
            <label for="tipo" class="label">Tipo</label>
            <select id="tipo" name="tipo" class="input">
                <option value="">Todos</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(request('tipo') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-40">
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

    @if ($users->isEmpty())
        <x-empty-state title="Nenhum utilizador encontrado" />
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <caption class="sr-only">Utilizadores registados</caption>
                    <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold">Utilizador</th>
                            <th scope="col" class="px-5 py-3 font-semibold">Tipo</th>
                            <th scope="col" class="px-5 py-3 font-semibold">Estado</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Reclamações</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">Registo</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($users as $user)
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-ink-900">{{ $user->publicDisplayName() }}</p>
                                    <p class="text-xs text-ink-500">{{ $user->email }}</p>
                                </td>
                                <td class="px-5 py-3 text-ink-600">{{ $user->type->label() }}</td>
                                <td class="px-5 py-3">
                                    <span class="badge {{ $user->isActive() ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
                                        {{ $user->status->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right text-ink-600">{{ $user->complaints_count }}</td>
                                <td class="px-5 py-3 text-right text-xs text-ink-400">
                                    {{ $user->created_at?->translatedFormat('j M Y') }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.users.show', $user) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $users->links() }}
    @endif
@endsection
