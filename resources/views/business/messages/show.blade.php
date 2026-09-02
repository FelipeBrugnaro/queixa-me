@extends('layouts.panel')

@section('panel-heading')
    <a href="{{ route('business.messages.index') }}" class="text-sm font-medium text-ink-500 hover:text-ink-800">
        <span aria-hidden="true">&larr;</span> Mensagens
    </a>
    <h1 class="mt-2 text-xl font-bold">{{ $conversation->user?->publicDisplayName() }}</h1>
    <p class="text-sm text-ink-500">{{ $conversation->title() }}</p>
@endsection

@section('panel')

    @if ($conversation->complaint)
        <div class="card mb-6">
            <div class="card-body flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-ink-600">
                    Reclamação associada
                    <strong class="font-medium text-ink-800">{{ $conversation->complaint->reference }}</strong>
                </p>
                <a href="{{ route('business.complaints.show', $conversation->complaint->uuid) }}" class="btn btn-secondary btn-sm">
                    Abrir reclamação
                </a>
            </div>
        </div>
    @endif

    <div class="rounded-xl bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-900 ring-1 ring-inset ring-amber-200">
        Este canal destina-se exclusivamente a tratar esta reclamação. Usá-lo para fins comerciais
        ou para pressionar o consumidor implica suspensão da conta.
    </div>

    <ol class="mt-6 space-y-4">
        @foreach ($conversation->messages as $message)
            <li class="flex {{ $message->isFromCompany() ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-lg rounded-2xl px-4 py-3 text-sm
                            {{ $message->isFromCompany() ? 'bg-brand-600 text-white' : 'bg-white ring-1 ring-ink-200' }}">
                    <p class="text-xs font-semibold {{ $message->isFromCompany() ? 'text-brand-100' : 'text-ink-500' }}">
                        {{ $message->displayName() }}
                    </p>
                    <p class="mt-1 whitespace-pre-line">{{ $message->body }}</p>
                    <p class="mt-1.5 text-[11px] {{ $message->isFromCompany() ? 'text-brand-200' : 'text-ink-400' }}">
                        {{ $message->created_at?->translatedFormat('j M, H:i') }}
                    </p>
                </div>
            </li>
        @endforeach
    </ol>

    @if ($conversation->isClosed())
        <div class="mt-6 rounded-xl bg-ink-100 px-4 py-3 text-center text-sm text-ink-600">
            O consumidor encerrou esta conversa. Podes continuar a responder publicamente na reclamação.
        </div>
    @else
        <form method="POST" action="{{ route('business.messages.store', $conversation->uuid) }}" class="card mt-6" data-guard-submit>
            @csrf
            <div class="card-body">
                <label for="body" class="label">Resposta</label>
                <textarea id="body" name="body" rows="4" required maxlength="4000"
                          class="input textarea @error('body') input-error @enderror">{{ old('body') }}</textarea>
                @error('body')<p class="error-text">{{ $message }}</p>@enderror

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="btn btn-primary">Enviar</button>
                </div>
            </div>
        </form>
    @endif
@endsection
