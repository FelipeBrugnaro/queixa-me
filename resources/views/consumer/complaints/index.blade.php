@extends('layouts.panel')

@section('panel-heading')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">As minhas reclamações</h1>
        <a href="{{ route('complaints.create') }}" class="btn btn-primary btn-sm">Nova reclamação</a>
    </div>
@endsection

@section('panel')
    @if ($complaints->isEmpty())
        <x-empty-state
            title="Ainda não tens reclamações"
            description="Conta-nos o que aconteceu e damos à empresa a oportunidade de resolver.">
            <a href="{{ route('complaints.create') }}" class="btn btn-primary">Fazer uma reclamação</a>
        </x-empty-state>
    @else
        <ul class="space-y-3">
            @foreach ($complaints as $complaint)
                <li class="card card-hover">
                    <a href="{{ route('consumer.complaints.show', $complaint->uuid) }}" class="block p-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge {{ $complaint->moderation_status->badgeClasses() }}">{{ $complaint->moderation_status->label() }}</span>
                            @if ($complaint->isPublished())
                                <span class="badge {{ $complaint->stage->badgeClasses() }}">{{ $complaint->stage->label() }}</span>
                            @endif
                            <span class="ml-auto text-xs text-ink-400">{{ $complaint->reference }}</span>
                        </div>

                        <p class="mt-2 font-medium text-ink-900">{{ $complaint->title ?: 'Rascunho sem título' }}</p>

                        <p class="mt-0.5 text-sm text-ink-500">
                            {{ $complaint->company?->name ?? $complaint->company_name_raw }}
                            <span aria-hidden="true">·</span>
                            {{ $complaint->created_at?->translatedFormat('j M Y') }}
                            @if ($complaint->replies_count > 0)
                                <span aria-hidden="true">·</span> {{ $complaint->replies_count }} {{ $complaint->replies_count === 1 ? 'resposta' : 'respostas' }}
                            @endif
                        </p>

                        @if ($complaint->rating)
                            <div class="mt-2"><x-stars :rating="$complaint->rating" show-value /></div>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>

        {{ $complaints->links() }}
    @endif
@endsection
