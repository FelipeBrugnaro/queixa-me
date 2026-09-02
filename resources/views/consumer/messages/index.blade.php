@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Mensagens e notificações</h1>
    <p class="mt-1 text-sm text-ink-600">Conversas privadas com empresas e avisos do sistema.</p>
@endsection

@section('panel')

    <section aria-labelledby="conversas">
        <h2 id="conversas" class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500">Conversas</h2>

        @if ($conversations->isEmpty())
            <x-empty-state
                title="Ainda não tens conversas"
                description="As empresas podem contactar-te em privado para tratar dados que não devem ser públicos." />
        @else
            <ul class="space-y-3">
                @foreach ($conversations as $conversation)
                    <li class="card card-hover">
                        <a href="{{ route('consumer.messages.show', $conversation->uuid) }}" class="flex items-center gap-4 p-5">
                            <x-company-avatar :company="$conversation->company" size="md" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate font-medium text-ink-900">{{ $conversation->company?->name }}</p>
                                    @if ($conversation->user_unread_count > 0)
                                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">
                                            {{ $conversation->user_unread_count }}
                                        </span>
                                    @endif
                                    @if ($conversation->isClosed())
                                        <span class="badge bg-ink-100 text-ink-600 ring-ink-200">Encerrada</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 truncate text-sm text-ink-500">{{ $conversation->title() }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-ink-400">
                                {{ $conversation->last_message_at?->diffForHumans() }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{ $conversations->links() }}
        @endif
    </section>

    <section class="mt-10" aria-labelledby="notificacoes">
        <h2 id="notificacoes" class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500">
            Notificações
            @if ($unreadNotifications > 0)
                <span class="ml-1 rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-700">{{ $unreadNotifications }}</span>
            @endif
        </h2>

        @if ($notifications->isEmpty())
            <div class="card">
                <div class="card-body text-center text-sm text-ink-500">Sem notificações.</div>
            </div>
        @else
            <ul class="space-y-2">
                @foreach ($notifications as $notification)
                    <li class="card">
                        <div class="flex items-start gap-3 p-4">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-ink-200' : 'bg-brand-500' }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-ink-800">{{ $notification->data['message'] ?? 'Notificação' }}</p>
                                <p class="mt-0.5 text-xs text-ink-400">{{ $notification->created_at?->diffForHumans() }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
