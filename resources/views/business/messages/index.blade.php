@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Mensagens privadas</h1>
    <p class="mt-1 text-sm text-ink-600">
        Canal para tratar o que não deve ser público: números de encomenda, moradas e dados de reembolso.
    </p>
@endsection

@section('panel')
    @if ($conversations->isEmpty())
        <x-empty-state
            title="Ainda não há conversas"
            description="Podes iniciar uma conversa privada a partir de qualquer reclamação recebida.">
            <a href="{{ route('business.complaints.index') }}" class="btn btn-secondary">Ver reclamações</a>
        </x-empty-state>
    @else
        <ul class="space-y-3">
            @foreach ($conversations as $conversation)
                <li class="card card-hover">
                    <a href="{{ route('business.messages.show', $conversation->uuid) }}" class="flex items-center gap-4 p-5">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-ink-100 text-sm font-bold text-ink-600" aria-hidden="true">
                            {{ $conversation->user?->initials() ?? '?' }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate font-medium text-ink-900">{{ $conversation->user?->publicDisplayName() }}</p>
                                @if ($conversation->company_unread_count > 0)
                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">
                                        {{ $conversation->company_unread_count }}
                                    </span>
                                @endif
                                @if ($conversation->isClosed())
                                    <span class="badge bg-ink-100 text-ink-600 ring-ink-200">Encerrada pelo consumidor</span>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate text-sm text-ink-500">{{ $conversation->title() }}</p>
                            @if ($conversation->complaint)
                                <p class="mt-0.5 text-xs text-ink-400">Ref. {{ $conversation->complaint->reference }}</p>
                            @endif
                        </div>

                        <span class="shrink-0 text-xs text-ink-400">{{ $conversation->last_message_at?->diffForHumans() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        {{ $conversations->links() }}
    @endif
@endsection
