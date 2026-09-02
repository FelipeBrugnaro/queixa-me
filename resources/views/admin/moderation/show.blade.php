@extends('layouts.panel')

@php use App\Domain\Complaints\Enums\ModerationStatus; @endphp

@section('panel-heading')
    <a href="{{ route('admin.moderation.index') }}" class="text-sm font-medium text-ink-500 hover:text-ink-800">
        <span aria-hidden="true">&larr;</span> Fila de moderação
    </a>
    <h1 class="mt-2 text-2xl font-bold">{{ $complaint->title }}</h1>
    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
        <span class="badge {{ $complaint->moderation_status->badgeClasses() }}">{{ $complaint->moderation_status->label() }}</span>
        <span class="text-ink-400">Ref. {{ $complaint->reference }}</span>
        <span class="text-ink-400">Submetida {{ $complaint->submitted_at?->translatedFormat('j M Y, H:i') }}</span>
    </div>
@endsection

@section('panel')
<div class="lg:grid lg:grid-cols-[1fr_18rem] lg:gap-6">

    <div class="min-w-0 space-y-6">

        {{-- Alerta de dados sensíveis --}}
        @if ($findings)
            <div class="card ring-rose-300">
                <div class="card-body">
                    <h2 class="font-semibold text-rose-900">Possíveis dados sensíveis no texto</h2>
                    <p class="mt-1 text-sm text-ink-600">
                        Deteção automática. Confirma manualmente — pode haver falsos positivos.
                    </p>
                    <ul class="mt-3 space-y-1.5 text-sm">
                        @foreach ($findings as $type => $finding)
                            <li class="flex items-center justify-between gap-3 rounded-lg bg-rose-50 px-3 py-2">
                                <span class="font-medium text-rose-900">{{ $type }}</span>
                                <span class="font-mono text-xs text-rose-700">
                                    {{ implode(', ', $finding['samples']) }} ({{ $finding['count'] }}x)
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Conteúdo --}}
        <div class="card">
            <div class="card-body">
                <h2 class="font-semibold">Conteúdo submetido</h2>
                <div class="prose-qm mt-3 whitespace-pre-line text-sm">{{ $complaint->description }}</div>

                @if ($complaint->desired_resolution)
                    <div class="mt-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Resolução pretendida</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink-600">{{ $complaint->desired_resolution }}</p>
                    </div>
                @endif

                @if ($complaint->extra_info)
                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Informações adicionais</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink-600">{{ $complaint->extra_info }}</p>
                    </div>
                @endif

                @if ($complaint->attachments->isNotEmpty())
                    <div class="mt-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                            Anexos ({{ $complaint->attachments->count() }})
                        </p>
                        <ul class="mt-2 flex flex-wrap gap-2">
                            @foreach ($complaint->attachments as $attachment)
                                <li>
                                    <a href="{{ $attachment->downloadUrl() }}" class="btn btn-secondary btn-sm">
                                        {{ \Illuminate\Support\Str::limit($attachment->original_name, 28) }}
                                        <span class="text-ink-400">{{ $attachment->humanSize() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        {{-- Decisões --}}
        @if ($complaint->moderation_status->isPending())
            <div class="card">
                <div class="card-body space-y-6">
                    <h2 class="font-semibold">Decisão</h2>

                    {{-- Aprovar --}}
                    <form method="POST" action="{{ route('admin.moderation.approve', $complaint->uuid) }}"
                          class="rounded-xl bg-emerald-50 p-4 ring-1 ring-inset ring-emerald-200" data-guard-submit>
                        @csrf
                        <p class="text-sm font-semibold text-emerald-900">Aprovar e publicar</p>
                        <input type="text" name="notes" maxlength="1000" placeholder="Nota interna (opcional)"
                               class="input mt-3 text-sm">
                        <button type="submit" class="btn btn-primary mt-3 w-full bg-emerald-600 hover:bg-emerald-700">
                            Aprovar
                        </button>
                    </form>

                    {{-- Pedir alterações --}}
                    <form method="POST" action="{{ route('admin.moderation.changes', $complaint->uuid) }}"
                          class="rounded-xl bg-amber-50 p-4 ring-1 ring-inset ring-amber-200" data-guard-submit>
                        @csrf
                        <p class="text-sm font-semibold text-amber-900">Devolver ao autor para alterações</p>
                        <select name="reason" required class="input mt-3 text-sm">
                            <option value="">Motivo…</option>
                            @foreach ($reasons as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea name="message" rows="3" maxlength="2000"
                                  placeholder="Mensagem para o autor (obrigatória em «Outro motivo»)"
                                  class="input mt-2 text-sm"></textarea>
                        <button type="submit" class="btn btn-secondary mt-3 w-full">Pedir alterações</button>
                    </form>

                    {{-- Rejeitar --}}
                    <form method="POST" action="{{ route('admin.moderation.reject', $complaint->uuid) }}"
                          class="rounded-xl bg-rose-50 p-4 ring-1 ring-inset ring-rose-200" data-guard-submit>
                        @csrf
                        <p class="text-sm font-semibold text-rose-900">Rejeitar</p>
                        <p class="mt-1 text-xs text-rose-800">
                            Só quando o conteúdo não pode ser publicado em nenhuma versão razoável.
                        </p>
                        <select name="reason" required class="input mt-3 text-sm">
                            <option value="">Motivo…</option>
                            @foreach ($reasons as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea name="message" rows="3" maxlength="2000" placeholder="Mensagem para o autor"
                                  class="input mt-2 text-sm"></textarea>
                        <button type="submit" class="btn btn-danger mt-3 w-full">Rejeitar</button>
                    </form>
                </div>
            </div>
        @elseif ($complaint->moderation_status === ModerationStatus::Approved)
            <form method="POST" action="{{ route('admin.moderation.remove', $complaint->uuid) }}" class="card" data-guard-submit>
                @csrf
                <div class="card-body">
                    <h2 class="font-semibold text-rose-900">Remover conteúdo publicado</h2>
                    <p class="mt-1 text-xs text-ink-500">
                        Apenas para conteúdo ilícito ou denúncia procedente. Fica registado em auditoria.
                    </p>
                    <textarea name="reason" rows="3" required minlength="10" maxlength="1000"
                              placeholder="Fundamentação da remoção"
                              class="input textarea mt-3 text-sm"></textarea>
                    <button type="submit" class="btn btn-danger mt-3">Remover</button>
                </div>
            </form>
        @endif

        {{-- Histórico de decisões --}}
        @if ($complaint->moderationReviews->isNotEmpty())
            <div class="card">
                <div class="card-body">
                    <h2 class="font-semibold">Decisões anteriores</h2>
                    <ul class="mt-3 space-y-3 text-sm">
                        @foreach ($complaint->moderationReviews as $review)
                            <li class="rounded-lg bg-ink-50 p-3">
                                <p class="font-medium text-ink-800">
                                    {{ $review->action }}
                                    @if ($review->reason_code)
                                        <span class="text-ink-500">· {{ $review->reason_code }}</span>
                                    @endif
                                </p>
                                @if ($review->message_to_author)
                                    <p class="mt-1 text-ink-600">{{ $review->message_to_author }}</p>
                                @endif
                                <p class="mt-1 text-xs text-ink-400">
                                    {{ $review->moderator?->name ?? 'Sistema' }} ·
                                    {{ $review->created_at?->translatedFormat('j M Y, H:i') }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    {{-- Contexto lateral --}}
    <aside class="mt-6 space-y-4 lg:mt-0">
        <div class="card">
            <div class="card-body">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-500">Empresa</h2>
                <div class="mt-3 flex items-center gap-3">
                    <x-company-avatar :company="$complaint->company" size="md" />
                    <div class="min-w-0">
                        <p class="truncate font-medium text-ink-900">
                            {{ $complaint->company?->name ?? $complaint->company_name_raw }}
                        </p>
                        <p class="text-xs text-ink-500">{{ $complaint->company?->status->label() }}</p>
                    </div>
                </div>
                @if ($complaint->company)
                    <a href="{{ route('admin.companies.edit', $complaint->company) }}" class="btn btn-secondary btn-sm mt-3 w-full">
                        Gerir ficha
                    </a>
                @endif
            </div>
        </div>

        {{-- Padrão de comportamento do autor: sinal-chave para detetar campanhas --}}
        <div class="card">
            <div class="card-body">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-500">Autor</h2>
                <p class="mt-2 font-medium text-ink-900">{{ $complaint->user?->publicDisplayName() }}</p>
                <p class="text-xs text-ink-500">
                    Conta criada {{ $complaint->user?->created_at?->translatedFormat('j M Y') }}
                </p>

                <dl class="mt-4 space-y-2 text-sm">
                    @foreach ([
                        ['Total de reclamações', $authorContext['total']],
                        ['Últimos 30 dias', $authorContext['last_30_days']],
                        ['Sobre esta empresa', $authorContext['same_company']],
                        ['Rejeitadas', $authorContext['rejected']],
                    ] as [$label, $value])
                        <div class="flex justify-between gap-2">
                            <dt class="text-ink-500">{{ $label }}</dt>
                            <dd class="font-semibold text-ink-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($authorContext['last_30_days'] >= 5 || $authorContext['same_company'] >= 3)
                    <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">
                        Volume invulgar. Verifica se não se trata de campanha coordenada ou de
                        reclamações duplicadas sobre o mesmo caso.
                    </p>
                @endif

                @if ($complaint->user)
                    <a href="{{ route('admin.users.show', $complaint->user) }}" class="btn btn-secondary btn-sm mt-3 w-full">
                        Ver utilizador
                    </a>
                @endif
            </div>
        </div>

        {{-- Dados de contacto: visíveis à moderação para verificar coerência --}}
        @if ($complaint->contactDetails && ! $complaint->contactDetails->isPurged())
            <div class="card">
                <div class="card-body">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-500">Contacto (privado)</h2>
                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div>
                            <dt class="text-xs text-ink-400">Nome</dt>
                            <dd class="text-ink-800">{{ $complaint->contactDetails->fullName() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-400">Email</dt>
                            <dd class="break-all text-ink-800">{{ $complaint->contactDetails->email }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        @endif
    </aside>
</div>
@endsection
