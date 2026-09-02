@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">A minha atividade</h1>
    <p class="mt-1 text-sm text-ink-600">Tudo o que aconteceu nas tuas reclamações, por ordem cronológica.</p>
@endsection

@section('panel')
    @if ($events->isEmpty())
        <x-empty-state
            title="Ainda não há atividade"
            description="Assim que submeteres a primeira reclamação, o histórico aparece aqui.">
            <a href="{{ route('complaints.create') }}" class="btn btn-primary">Fazer uma reclamação</a>
        </x-empty-state>
    @else
        <ol class="relative">
            @foreach ($events as $event)
                <li class="relative flex gap-4 pb-8 last:pb-0">
                    @unless ($loop->last)
                        <span class="absolute left-[15px] top-8 h-full w-px bg-ink-200" aria-hidden="true"></span>
                    @endunless

                    <span class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full
                                 {{ $event->type->icon() === 'check' ? 'bg-emerald-100 text-emerald-700' : ($event->type->icon() === 'x' ? 'bg-rose-100 text-rose-700' : 'bg-ink-100 text-ink-500') }}">
                        <span class="size-2 rounded-full bg-current"></span>
                    </span>

                    <div class="min-w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-ink-900">{{ $event->type->label() }}</p>

                        @if ($event->summary)
                            <p class="mt-0.5 text-sm text-ink-600">{{ $event->summary }}</p>
                        @endif

                        @if ($event->complaint)
                            <a href="{{ route('consumer.complaints.show', $event->complaint->uuid) }}"
                               class="mt-1 inline-flex text-sm text-brand-700 hover:text-brand-800">
                                {{ \Illuminate\Support\Str::limit($event->complaint->title ?: $event->complaint->reference, 70) }}
                            </a>
                            @if ($event->complaint->company)
                                <span class="text-sm text-ink-400"> · {{ $event->complaint->company->name }}</span>
                            @endif
                        @endif

                        <p class="mt-1 text-xs text-ink-400">
                            <time datetime="{{ $event->created_at?->toIso8601String() }}">
                                {{ $event->created_at?->translatedFormat('j M Y, H:i') }}
                            </time>
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>

        {{ $events->links() }}
    @endif
@endsection
