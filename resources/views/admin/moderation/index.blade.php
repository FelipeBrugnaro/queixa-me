@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Fila de moderação</h1>
    <p class="mt-1 text-sm text-ink-600">
        Ordenada por prioridade e depois por antiguidade. Reclamações com dados sensíveis detetados
        sobem automaticamente na fila.
    </p>
@endsection

@section('panel')

    <dl class="mb-6 grid grid-cols-3 gap-4">
        @foreach ([
            ['Por analisar', $counters['pending'], 'text-amber-700'],
            ['Com dados sensíveis', $counters['sensitive'], 'text-rose-700'],
            ['Mais antiga', $counters['oldest_hours'].'h', 'text-ink-900'],
        ] as [$label, $value, $tone])
            <div class="card">
                <div class="p-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-500">{{ $label }}</dt>
                    <dd class="mt-1 text-2xl font-bold {{ $tone }}">{{ $value }}</dd>
                </div>
            </div>
        @endforeach
    </dl>

    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="min-w-48 flex-1">
            <label for="q" class="label">Pesquisar</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="assunto ou descrição" class="input">
        </div>
        <label class="flex items-center gap-2 pb-2.5 text-sm text-ink-700">
            <input type="checkbox" name="sensiveis" value="1" class="checkbox" @checked(request()->boolean('sensiveis'))>
            Só com dados sensíveis
        </label>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
        <a href="{{ route('admin.moderation.index') }}" class="btn btn-ghost">Limpar</a>
    </form>

    @if ($queue->isEmpty())
        <x-empty-state title="Fila vazia" description="Não há reclamações à espera de análise." />
    @else
        <ul class="space-y-3">
            @foreach ($queue as $complaint)
                <li class="card card-hover {{ $complaint->sensitive_flags ? 'ring-rose-200' : '' }}">
                    <a href="{{ route('admin.moderation.show', $complaint->uuid) }}" class="block p-5">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="badge {{ $complaint->moderation_status->badgeClasses() }}">
                                {{ $complaint->moderation_status->label() }}
                            </span>

                            @if ($complaint->sensitive_flags)
                                <span class="badge bg-rose-50 text-rose-700 ring-rose-200">
                                    {{ implode(', ', array_keys($complaint->sensitive_flags)) }}
                                </span>
                            @endif

                            @if ($complaint->priority > 0)
                                <span class="badge bg-ink-100 text-ink-600 ring-ink-200">Prioridade {{ $complaint->priority }}</span>
                            @endif

                            <span class="ml-auto text-ink-400">
                                submetida {{ $complaint->submitted_at?->diffForHumans() }}
                            </span>
                        </div>

                        <p class="mt-2 font-medium text-ink-900">{{ $complaint->title }}</p>

                        <p class="mt-1 text-sm text-ink-500">
                            {{ $complaint->company?->name ?? $complaint->company_name_raw }}
                            <span aria-hidden="true">·</span>
                            {{ $complaint->user?->publicDisplayName() }}
                            <span aria-hidden="true">·</span>
                            conta criada {{ $complaint->user?->created_at?->diffForHumans() }}
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>

        {{ $queue->links() }}
    @endif
@endsection
