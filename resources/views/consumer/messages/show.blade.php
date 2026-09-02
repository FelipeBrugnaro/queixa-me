@extends('layouts.panel')

@section('panel-heading')
    <a href="{{ route('consumer.messages.index') }}" class="text-sm font-medium text-ink-500 hover:text-ink-800">
        <span aria-hidden="true">&larr;</span> Mensagens
    </a>
    <div class="mt-2 flex flex-wrap items-center gap-3">
        <x-company-avatar :company="$conversation->company" size="md" />
        <div class="min-w-0">
            <h1 class="text-xl font-bold">{{ $conversation->company?->name }}</h1>
            <p class="text-sm text-ink-500">{{ $conversation->title() }}</p>
        </div>
    </div>
@endsection

@section('panel')

    @if ($conversation->complaint)
        <div class="card mb-6">
            <div class="card-body flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-ink-600">
                    Conversa associada à reclamação
                    <strong class="font-medium text-ink-800">{{ $conversation->complaint->reference }}</strong>
                </p>
                <a href="{{ route('consumer.complaints.show', $conversation->complaint->uuid) }}" class="btn btn-secondary btn-sm">
                    Ver reclamação
                </a>
            </div>
        </div>
    @endif

    <div class="rounded-xl bg-brand-50 px-4 py-3 text-xs leading-relaxed text-brand-900 ring-1 ring-inset ring-brand-100">
        Esta conversa é privada e não aparece na página pública da reclamação. É o local certo para
        números de encomenda, moradas ou dados de reembolso.
    </div>

    <ol class="mt-6 space-y-4">
        @foreach ($conversation->messages as $message)
            <li class="flex {{ $message->isFromCompany() ? 'justify-start' : 'justify-end' }}">
                <div class="max-w-lg rounded-2xl px-4 py-3 text-sm
                            {{ $message->isFromCompany() ? 'bg-white ring-1 ring-ink-200' : 'bg-brand-600 text-white' }}">
                    <p class="text-xs font-semibold {{ $message->isFromCompany() ? 'text-ink-500' : 'text-brand-100' }}">
                        {{ $message->displayName() }}
                    </p>
                    <p class="mt-1 whitespace-pre-line">{{ $message->body }}</p>
                    <p class="mt-1.5 text-[11px] {{ $message->isFromCompany() ? 'text-ink-400' : 'text-brand-200' }}">
                        {{ $message->created_at?->translatedFormat('j M, H:i') }}
                    </p>
                </div>
            </li>
        @endforeach
    </ol>

    @if ($conversation->isClosed())
        <div class="mt-6 rounded-xl bg-ink-100 px-4 py-3 text-center text-sm text-ink-600">
            Esta conversa está encerrada. A empresa já não te pode enviar mensagens privadas.
        </div>
    @else
        <form method="POST" action="{{ route('consumer.messages.store', $conversation->uuid) }}" class="card mt-6" data-guard-submit>
            @csrf
            <div class="card-body">
                <label for="body" class="label">A tua mensagem</label>
                <textarea id="body" name="body" rows="4" required maxlength="4000"
                          class="input textarea @error('body') input-error @enderror">{{ old('body') }}</textarea>
                @error('body')<p class="error-text">{{ $message }}</p>@enderror

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="btn btn-primary">Enviar</button>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('consumer.messages.close', $conversation->uuid) }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-xs text-ink-400 underline underline-offset-2 hover:text-rose-600">
                Encerrar esta conversa
            </button>
        </form>
    @endif
@endsection
