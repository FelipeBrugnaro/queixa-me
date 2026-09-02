@extends('layouts.panel')

@section('panel-heading')
    <a href="{{ route('business.complaints.index') }}" class="text-sm font-medium text-ink-500 hover:text-ink-800">
        <span aria-hidden="true">&larr;</span> Reclamações
    </a>
    <h1 class="mt-2 text-2xl font-bold">{{ $complaint->title }}</h1>
    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
        <span class="badge {{ $complaint->stage->badgeClasses() }}">{{ $complaint->stage->label() }}</span>
        <span class="text-ink-400">Ref. {{ $complaint->reference }}</span>
        <a href="{{ $complaint->url() }}" class="font-medium text-brand-700 hover:text-brand-800">Ver página pública</a>
    </div>
@endsection

@section('panel')

    @if ($complaint->awaitsCompanyReply())
        <div class="mb-6 rounded-xl {{ $complaint->responseSlaBreached() ? 'bg-rose-50 text-rose-900 ring-rose-200' : 'bg-amber-50 text-amber-900 ring-amber-200' }} px-4 py-3 text-sm ring-1 ring-inset">
            Esta reclamação está sem resposta há <strong class="font-semibold">{{ $complaint->daysWaitingForReply() }} dias</strong>.
            @if ($complaint->responseSlaBreached())
                Já ultrapassou o prazo de {{ config('queixame.complaints.response_sla_days') }} dias e está a contar como não respondida no vosso índice.
            @endif
        </div>
    @endif

    {{-- Reclamação --}}
    <div class="card">
        <div class="card-body">
            <p class="text-xs uppercase tracking-wide text-ink-400">
                Publicada {{ $complaint->published_at?->translatedFormat('j \d\e F \d\e Y') }}
                @if ($complaint->occurred_on)
                    · ocorrência a {{ $complaint->occurred_on->translatedFormat('j/m/Y') }}
                @endif
            </p>

            <div class="prose-qm mt-4 whitespace-pre-line text-sm">{{ $complaint->description }}</div>

            @if ($complaint->desired_resolution)
                <div class="mt-5 rounded-xl bg-brand-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-800">O que o consumidor pretende</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-brand-900">{{ $complaint->desired_resolution }}</p>
                </div>
            @endif

            @if ($complaint->extra_info)
                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Informações adicionais</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-ink-600">{{ $complaint->extra_info }}</p>
                </div>
            @endif

            <dl class="mt-5 grid gap-3 border-t border-ink-100 pt-4 text-sm sm:grid-cols-3">
                @if ($complaint->purchase_reference)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ink-400">Nº encomenda / contrato</dt>
                        <dd class="font-medium text-ink-800">{{ $complaint->purchase_reference }}</dd>
                    </div>
                @endif
                @if ($complaint->amount_involved)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ink-400">Valor</dt>
                        <dd class="font-medium text-ink-800">{{ number_format((float) $complaint->amount_involved, 2, ',', ' ') }} €</dd>
                    </div>
                @endif
                @if ($complaint->district)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ink-400">Localização</dt>
                        <dd class="font-medium text-ink-800">{{ $complaint->district }}</dd>
                    </div>
                @endif
            </dl>

            @if ($complaint->attachments->isNotEmpty())
                <div class="mt-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Anexos</p>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($complaint->attachments as $attachment)
                            <li>
                                <a href="{{ $attachment->downloadUrl() }}" class="btn btn-secondary btn-sm">
                                    {{ \Illuminate\Support\Str::limit($attachment->original_name, 30) }}
                                    <span class="text-ink-400">{{ $attachment->humanSize() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    {{-- Dados de contacto --}}
    <div class="card mt-6">
        <div class="card-body">
            <h2 class="font-semibold">Dados de contacto do reclamante</h2>

            @if ($canSeeContact)
                <p class="mt-1 text-xs text-ink-500">
                    Transmitidos com consentimento explícito do titular, exclusivamente para tratar esta reclamação.
                    Não os podes usar para outros fins.
                </p>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ink-400">Nome</dt>
                        <dd class="font-medium text-ink-800">{{ $complaint->contactDetails->fullName() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ink-400">Email</dt>
                        <dd class="font-medium text-ink-800">{{ $complaint->contactDetails->email }}</dd>
                    </div>
                    @if ($complaint->contactDetails->phone)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-ink-400">Telefone</dt>
                            <dd class="font-medium text-ink-800">{{ $complaint->contactDetails->phone }}</dd>
                        </div>
                    @endif
                    @if ($complaint->contactDetails->address)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-ink-400">Morada</dt>
                            <dd class="font-medium text-ink-800">
                                {{ $complaint->contactDetails->address }},
                                {{ $complaint->contactDetails->postal_code }}
                                {{ $complaint->contactDetails->locality }}
                            </dd>
                        </div>
                    @endif
                </dl>
            @else
                <p class="mt-2 rounded-lg bg-ink-100 px-3 py-2 text-sm text-ink-600">
                    Os dados de contacto não estão disponíveis: o consentimento não foi dado ou já
                    foi cumprido o prazo de conservação. Usa a mensagem privada para contactar o consumidor.
                </p>
            @endif
        </div>
    </div>

    {{-- Fio de respostas --}}
    @if ($complaint->replies->isNotEmpty())
        <section class="mt-6" aria-labelledby="fio">
            <h2 id="fio" class="mb-3 text-lg font-semibold">Fio público</h2>
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

    {{-- Ações --}}
    @if ($complaint->canBeRepliedByCompany())
        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('business.complaints.reply', $complaint->uuid) }}" class="card" data-guard-submit>
                @csrf
                <div class="card-body">
                    <h2 class="font-semibold">Responder publicamente</h2>
                    <p class="mt-1 text-xs text-ink-500">
                        Esta resposta fica visível na página pública. Não incluas dados pessoais do consumidor.
                    </p>
                    <textarea name="body" rows="6" required
                              minlength="{{ config('queixame.complaints.reply_min') }}"
                              maxlength="{{ config('queixame.complaints.reply_max') }}"
                              placeholder="Lamentamos o sucedido. Já identificámos o processo e…"
                              class="input textarea mt-3">{{ old('body') }}</textarea>
                    @error('body')<p class="error-text">{{ $message }}</p>@enderror

                    <input type="text" name="display_name" maxlength="120" placeholder="Assinatura (ex.: Apoio ao Cliente)"
                           class="input mt-3 text-sm" value="{{ old('display_name') }}">

                    <button type="submit" class="btn btn-primary mt-4 w-full">Publicar resposta</button>
                </div>
            </form>

            <div class="space-y-4">
                <form method="POST" action="{{ route('business.complaints.propose', $complaint->uuid) }}" class="card" data-guard-submit>
                    @csrf
                    <div class="card-body">
                        <h2 class="font-semibold">Propor uma solução</h2>
                        <p class="mt-1 text-xs text-ink-500">
                            Abre a janela em que o consumidor confirma se o problema ficou resolvido.
                            Só ele o pode fazer.
                        </p>
                        <textarea name="body" rows="4" required minlength="20" maxlength="{{ config('queixame.complaints.reply_max') }}"
                                  placeholder="Vamos proceder ao reembolso integral de 149,90 € até…"
                                  class="input textarea mt-3"></textarea>
                        <button type="submit" class="btn btn-secondary mt-4 w-full">Propor solução</button>
                    </div>
                </form>

                @unless ($complaint->conversation)
                    <form method="POST" action="{{ route('business.complaints.message', $complaint->uuid) }}" class="card" data-guard-submit>
                        @csrf
                        <div class="card-body">
                            <h2 class="font-semibold">Mensagem privada</h2>
                            <p class="mt-1 text-xs text-ink-500">
                                Para tratar dados que não devem ser públicos.
                            </p>
                            <textarea name="body" rows="3" required minlength="20" maxlength="4000"
                                      placeholder="Para tratarmos do reembolso, precisamos de confirmar…"
                                      class="input textarea mt-3"></textarea>
                            <button type="submit" class="btn btn-secondary mt-4 w-full">Enviar mensagem privada</button>
                        </div>
                    </form>
                @else
                    <div class="card">
                        <div class="card-body flex items-center justify-between gap-3">
                            <p class="text-sm text-ink-600">Já existe uma conversa privada.</p>
                            <a href="{{ route('business.messages.show', $complaint->conversation->uuid) }}" class="btn btn-secondary btn-sm">Abrir</a>
                        </div>
                    </div>
                @endunless
            </div>
        </div>
    @endif

    {{-- Denúncia --}}
    <details class="card mt-6">
        <summary class="cursor-pointer px-5 py-4 text-sm font-medium text-ink-700">
            Denunciar esta reclamação
        </summary>
        <form method="POST" action="{{ route('business.complaints.report', $complaint->uuid) }}"
              class="border-t border-ink-100 p-5" data-guard-submit>
            @csrf
            <p class="text-xs leading-relaxed text-ink-500">
                Denuncia apenas conteúdo que exponha dados pessoais, seja ofensivo ou comprovadamente
                falso. Reclamações desfavoráveis mas legítimas não são removidas — para essas, usa o
                direito de resposta.
            </p>

            <div class="mt-4 space-y-3">
                <x-field name="reason" label="Motivo" type="select" required :options="$reportReasons" />
                <x-field name="details" label="Fundamentação" type="textarea" rows="4" required
                         hint="Explica concretamente o que está errado e, se possível, junta prova." />
            </div>

            <button type="submit" class="btn btn-secondary mt-4">Submeter denúncia</button>
        </form>
    </details>

    {{-- Timeline --}}
    <section class="card mt-6" aria-labelledby="historico">
        <div class="card-body">
            <h2 id="historico" class="text-lg font-semibold">Histórico</h2>
            <ol class="mt-4 space-y-3">
                @foreach ($complaint->publicEvents as $event)
                    <li class="flex gap-3 text-sm">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-ink-300"></span>
                        <div>
                            <p class="font-medium text-ink-800">{{ $event->type->label() }}</p>
                            <p class="text-xs text-ink-400">{{ $event->created_at?->translatedFormat('j M Y, H:i') }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
@endsection
