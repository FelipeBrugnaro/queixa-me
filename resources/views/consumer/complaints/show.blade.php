@extends('layouts.panel')

@php
    use App\Domain\Complaints\Enums\ComplaintStage;
    use App\Domain\Complaints\Enums\ModerationStatus;
    use App\Domain\Moderation\Enums\RejectionReason;

    $awaitingConfirmation = $complaint->resolution_proposed_at !== null
        && $complaint->resolved_at === null
        && ! in_array($complaint->stage, [ComplaintStage::Closed, ComplaintStage::Unresolved], true);
@endphp

@section('panel-heading')
    <a href="{{ route('consumer.complaints.index') }}" class="text-sm font-medium text-ink-500 hover:text-ink-800">
        <span aria-hidden="true">&larr;</span> As minhas reclamações
    </a>
    <h1 class="mt-2 text-2xl font-bold">{{ $complaint->title ?: 'Reclamação '.$complaint->reference }}</h1>
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="badge {{ $complaint->moderation_status->badgeClasses() }}">{{ $complaint->moderation_status->label() }}</span>
        @if ($complaint->isPublished())
            <span class="badge {{ $complaint->stage->badgeClasses() }}">{{ $complaint->stage->label() }}</span>
        @endif
        <span class="text-xs text-ink-400">Ref. {{ $complaint->reference }}</span>
        @if ($complaint->isPublished())
            <a href="{{ $complaint->url() }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">Ver página pública</a>
        @endif
    </div>
@endsection

@section('panel')

    {{-- Moderação pediu alterações --}}
    @if ($complaint->moderation_status === ModerationStatus::ChangesRequested && $latestReview)
        <div class="card mb-6 ring-amber-300">
            <div class="card-body">
                <h2 class="font-semibold text-amber-900">A tua reclamação precisa de alterações</h2>
                @php $reason = RejectionReason::tryFrom((string) $latestReview->reason_code); @endphp
                @if ($reason)
                    <p class="mt-2 text-sm font-medium text-ink-800">{{ $reason->label() }}</p>
                    <p class="mt-1 text-sm text-ink-600">{{ $reason->guidanceForAuthor() }}</p>
                @endif
                @if ($latestReview->message_to_author)
                    <p class="mt-3 rounded-lg bg-ink-50 p-3 text-sm text-ink-700">{{ $latestReview->message_to_author }}</p>
                @endif
                <a href="{{ route('complaints.wizard.description', $complaint->uuid) }}" class="btn btn-primary mt-4">
                    Corrigir e reenviar
                </a>
            </div>
        </div>
    @endif

    {{-- Rejeitada --}}
    @if ($complaint->moderation_status === ModerationStatus::Rejected && $latestReview)
        <div class="card mb-6 ring-rose-300">
            <div class="card-body">
                <h2 class="font-semibold text-rose-900">Esta reclamação não foi publicada</h2>
                @php $reason = RejectionReason::tryFrom((string) $latestReview->reason_code); @endphp
                @if ($reason)
                    <p class="mt-2 text-sm text-ink-700">{{ $reason->label() }}</p>
                @endif
                @if ($latestReview->message_to_author)
                    <p class="mt-3 rounded-lg bg-ink-50 p-3 text-sm text-ink-700">{{ $latestReview->message_to_author }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Confirmar resolução --}}
    @if ($awaitingConfirmation)
        <div class="card mb-6 ring-emerald-300">
            <div class="card-body">
                <h2 class="font-semibold text-emerald-900">A empresa propôs uma solução</h2>
                <p class="mt-1.5 text-sm text-ink-600">
                    Só tu podes dar este problema como resolvido. A tua resposta conta para o índice
                    de satisfação de {{ $complaint->company?->name }}.
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    {{-- Resolvido --}}
                    <form method="POST" action="{{ route('consumer.complaints.resolve', $complaint->uuid) }}"
                          class="rounded-xl bg-emerald-50 p-4 ring-1 ring-inset ring-emerald-200" data-guard-submit>
                        @csrf
                        <p class="text-sm font-semibold text-emerald-900">Sim, ficou resolvido</p>

                        <fieldset class="mt-3">
                            <legend class="text-xs font-medium text-emerald-800">Como avalias a experiência?</legend>
                            <div class="mt-1.5 flex gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="{{ $i }}" class="peer sr-only" required>
                                        <span class="flex size-9 items-center justify-center rounded-lg bg-white text-sm font-semibold text-ink-600 ring-1 ring-emerald-200 peer-checked:bg-emerald-600 peer-checked:text-white">
                                            {{ $i }}
                                        </span>
                                    </label>
                                @endfor
                            </div>
                        </fieldset>

                        <label class="mt-3 flex items-center gap-2 text-xs text-emerald-900">
                            <input type="checkbox" name="would_recommend" value="1" class="checkbox">
                            Voltaria a comprar nesta empresa
                        </label>

                        <textarea name="comment" rows="2" maxlength="1000" placeholder="Comentário (opcional)"
                                  class="input mt-3 text-sm"></textarea>

                        <button type="submit" class="btn btn-primary mt-3 w-full bg-emerald-600 hover:bg-emerald-700">
                            Confirmar resolução
                        </button>
                    </form>

                    {{-- Não resolvido --}}
                    <form method="POST" action="{{ route('consumer.complaints.unresolved', $complaint->uuid) }}"
                          class="rounded-xl bg-ink-50 p-4 ring-1 ring-inset ring-ink-200" data-guard-submit>
                        @csrf
                        <p class="text-sm font-semibold text-ink-800">Não, o problema mantém-se</p>
                        <p class="mt-1 text-xs text-ink-500">A reclamação continua aberta e visível.</p>

                        <textarea name="comment" rows="3" maxlength="1000" placeholder="O que continua por resolver?"
                                  class="input mt-3 text-sm"></textarea>

                        <button type="submit" class="btn btn-secondary mt-3 w-full">Marcar como não resolvida</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Conteúdo --}}
    <div class="card">
        <div class="card-body">
            <div class="flex items-center gap-3">
                <x-company-avatar :company="$complaint->company" size="md" />
                <div class="min-w-0">
                    <p class="font-semibold">{{ $complaint->company?->name ?? $complaint->company_name_raw }}</p>
                    <p class="text-xs text-ink-500">
                        Submetida {{ $complaint->submitted_at?->translatedFormat('j M Y') ?? '—' }}
                        @if ($complaint->published_at)
                            <span aria-hidden="true">·</span> publicada {{ $complaint->published_at->translatedFormat('j M Y') }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="prose-qm mt-5 whitespace-pre-line text-sm">{{ $complaint->description }}</div>

            @if ($complaint->desired_resolution)
                <div class="mt-5 rounded-xl bg-brand-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-800">Resolução pretendida</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-brand-900">{{ $complaint->desired_resolution }}</p>
                </div>
            @endif

            @if ($complaint->attachments->isNotEmpty())
                <div class="mt-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Anexos</p>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($complaint->attachments as $attachment)
                            <li>
                                <a href="{{ $attachment->downloadUrl() }}" class="btn btn-secondary btn-sm">
                                    {{ \Illuminate\Support\Str::limit($attachment->original_name, 30) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    {{-- Respostas --}}
    @if ($complaint->replies->isNotEmpty())
        <section class="mt-6" aria-labelledby="respostas">
            <h2 id="respostas" class="mb-3 text-lg font-semibold">Respostas</h2>
            <ol class="space-y-3">
                @foreach ($complaint->replies as $reply)
                    <li class="card {{ $reply->isFromCompany() ? 'ring-brand-200' : '' }}">
                        <div class="card-body">
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="font-semibold text-ink-800">{{ $reply->displayName() }}</span>
                                @if ($reply->is_resolution_proposal)
                                    <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200">Proposta de solução</span>
                                @endif
                                <span class="ml-auto text-ink-400">{{ $reply->created_at?->translatedFormat('j M Y, H:i') }}</span>
                            </div>
                            <div class="prose-qm mt-2 whitespace-pre-line text-sm">{{ $reply->body }}</div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- Responder --}}
    @if ($complaint->isPublished() && $complaint->stage !== ComplaintStage::Closed)
        <form method="POST" action="{{ route('consumer.complaints.reply', $complaint->uuid) }}" class="card mt-6" data-guard-submit>
            @csrf
            <div class="card-body">
                <label for="body" class="label">Acrescentar uma resposta</label>
                <textarea id="body" name="body" rows="4" required
                          minlength="{{ config('queixame.complaints.reply_min') }}"
                          maxlength="{{ config('queixame.complaints.reply_max') }}"
                          placeholder="Atualiza o estado da situação ou responde à empresa."
                          class="input textarea @error('body') input-error @enderror">{{ old('body') }}</textarea>
                <p class="hint">Esta resposta é pública. Não incluas dados pessoais.</p>
                @error('body')<p class="error-text">{{ $message }}</p>@enderror

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="btn btn-primary">Publicar resposta</button>
                </div>
            </div>
        </form>
    @endif

    {{-- Conversa privada --}}
    @if ($complaint->conversation)
        <div class="card mt-6">
            <div class="card-body flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">Conversa privada com a empresa</p>
                    <p class="text-sm text-ink-500">Para dados que não devem ser públicos.</p>
                </div>
                <a href="{{ route('consumer.messages.show', $complaint->conversation->uuid) }}" class="btn btn-secondary">
                    Abrir conversa
                    @if ($complaint->conversation->user_unread_count > 0)
                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">
                            {{ $complaint->conversation->user_unread_count }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
    @endif

    {{-- Histórico --}}
    <section class="card mt-6" aria-labelledby="historico">
        <div class="card-body">
            <h2 id="historico" class="text-lg font-semibold">Histórico completo</h2>
            <ol class="mt-4 space-y-4">
                @foreach ($complaint->events as $event)
                    <li class="flex gap-3 text-sm">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-ink-300"></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-ink-800">{{ $event->type->label() }}</p>
                            @if ($event->summary)
                                <p class="text-ink-600">{{ $event->summary }}</p>
                            @endif
                            <p class="text-xs text-ink-400">{{ $event->created_at?->translatedFormat('j M Y, H:i') }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
@endsection
