@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Reclamações recebidas</h1>
    <p class="mt-1 text-sm text-ink-600">
        Ordenadas por urgência: primeiro as que ainda não têm resposta, e dentro dessas as mais antigas.
    </p>
@endsection

@section('panel')

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <nav aria-label="Filtrar" class="flex flex-wrap gap-2">
            @foreach ([
                'todas' => 'Todas',
                'por-responder' => 'Por responder',
                'atrasadas' => 'Fora do prazo',
                'em-curso' => 'Em curso',
                'resolvidas' => 'Resolvidas',
            ] as $value => $label)
                <a href="{{ route('business.complaints.index', ['filtro' => $value]) }}"
                   class="badge {{ $filter === $value ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-ink-700 ring-ink-200 hover:bg-ink-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <form method="GET" class="ml-auto flex items-center gap-2">
            <input type="hidden" name="filtro" value="{{ $filter }}">
            <label for="q" class="sr-only">Pesquisar</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Pesquisar…"
                   class="input py-1.5 text-sm sm:w-56">
            <button type="submit" class="btn btn-secondary btn-sm">Procurar</button>
        </form>
    </div>

    @if ($complaints->isEmpty())
        <x-empty-state
            title="Nenhuma reclamação neste filtro"
            description="Experimenta outro filtro ou limpa a pesquisa." />
    @else
        <ul class="space-y-3">
            @foreach ($complaints as $complaint)
                <li class="card card-hover {{ $complaint->responseSlaBreached() ? 'ring-rose-200' : '' }}">
                    <a href="{{ route('business.complaints.show', $complaint->uuid) }}" class="block p-5">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="badge {{ $complaint->stage->badgeClasses() }}">{{ $complaint->stage->label() }}</span>

                            @if ($complaint->awaitsCompanyReply())
                                <span class="badge {{ $complaint->responseSlaBreached() ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                    {{ $complaint->daysWaitingForReply() }} dias sem resposta
                                </span>
                            @endif

                            @if ($complaint->category)
                                <span class="text-ink-400">{{ $complaint->category->name }}</span>
                            @endif

                            <span class="ml-auto text-ink-400">{{ $complaint->reference }}</span>
                        </div>

                        <p class="mt-2 font-medium text-ink-900">{{ $complaint->title }}</p>
                        <p class="mt-1 line-clamp-2 text-sm text-ink-600">{{ $complaint->excerpt(180) }}</p>

                        <p class="mt-2 text-xs text-ink-400">
                            Publicada {{ $complaint->published_at?->translatedFormat('j M Y') }}
                            @if ($complaint->rating)
                                <span aria-hidden="true">·</span> avaliação {{ $complaint->rating }}/5
                            @endif
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>

        {{ $complaints->links() }}
    @endif
@endsection
