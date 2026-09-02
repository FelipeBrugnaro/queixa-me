@extends('layouts.app')

@section('content')
<div class="container-page py-8">
    <div class="lg:grid lg:grid-cols-[1fr_20rem] lg:gap-10">

        <article class="min-w-0">
            {{-- Cabeçalho --}}
            <header class="card">
                <div class="card-body">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge {{ $complaint->stage->badgeClasses() }}">{{ $complaint->stage->label() }}</span>
                        @if ($complaint->category)
                            <span class="badge bg-ink-100 text-ink-700 ring-ink-200">{{ $complaint->category->name }}</span>
                        @endif
                        <span class="text-xs text-ink-400">Ref. {{ $complaint->reference }}</span>
                    </div>

                    <h1 class="mt-4 text-2xl font-bold leading-tight sm:text-3xl">{{ $complaint->title }}</h1>

                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-500">
                        <span>Por <strong class="font-medium text-ink-700">{{ $complaint->authorDisplayName() }}</strong></span>
                        <span aria-hidden="true">·</span>
                        <span>Publicada a
                            <time datetime="{{ $complaint->published_at?->toDateString() }}">
                                {{ $complaint->published_at?->translatedFormat('j \d\e F \d\e Y') }}
                            </time>
                        </span>
                        @if ($complaint->occurred_on)
                            <span aria-hidden="true">·</span>
                            <span>Ocorrência a {{ $complaint->occurred_on->translatedFormat('j/m/Y') }}</span>
                        @endif
                        @if ($complaint->district)
                            <span aria-hidden="true">·</span>
                            <span>{{ $complaint->district }}</span>
                        @endif
                    </div>
                </div>
            </header>

            {{-- Aviso de ausência de resposta --}}
            @if ($complaint->awaitsCompanyReply() && $complaint->responseSlaBreached())
                <div class="mt-4 flex items-start gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
                    <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm0 3.5a.75.75 0 0 1 .75.75v4a.75.75 0 0 1-1.5 0v-4A.75.75 0 0 1 10 5.5Zm0 9a1.1 1.1 0 1 1 0-2.2 1.1 1.1 0 0 1 0 2.2Z" clip-rule="evenodd"/>
                    </svg>
                    <p>Esta reclamação está sem resposta da empresa há <strong>{{ $complaint->daysWaitingForReply() }} dias</strong>.</p>
                </div>
            @endif

            {{-- Descrição --}}
            <section class="card mt-6" aria-labelledby="descricao">
                <div class="card-body">
                    <h2 id="descricao" class="text-lg font-semibold">O que aconteceu</h2>
                    <div class="prose-qm mt-3 whitespace-pre-line">{{ $complaint->description }}</div>

                    @if ($complaint->desired_resolution)
                        <div class="mt-6 rounded-xl bg-brand-50 p-4 ring-1 ring-inset ring-brand-100">
                            <h3 class="text-sm font-semibold text-brand-900">Resolução pretendida</h3>
                            <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-brand-800">{{ $complaint->desired_resolution }}</p>
                        </div>
                    @endif

                    @if ($complaint->publicAttachments->isNotEmpty())
                        <div class="mt-6">
                            <h3 class="text-sm font-semibold">Anexos públicos</h3>
                            <ul class="mt-2 flex flex-wrap gap-2">
                                @foreach ($complaint->publicAttachments as $attachment)
                                    <li>
                                        <a href="{{ $attachment->downloadUrl() }}" class="btn btn-secondary btn-sm">
                                            {{ $attachment->original_name }}
                                            <span class="text-ink-400">{{ $attachment->humanSize() }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Respostas --}}
            <section class="mt-6" aria-labelledby="respostas">
                <h2 id="respostas" class="mb-4 text-lg font-semibold">
                    Respostas
                    <span class="ml-1 text-sm font-normal text-ink-500">({{ $complaint->publicReplies->count() }})</span>
                </h2>

                @if ($complaint->publicReplies->isEmpty())
                    <div class="card">
                        <div class="card-body text-center text-sm text-ink-500">
                            <p>A empresa ainda não respondeu publicamente a esta reclamação.</p>
                        </div>
                    </div>
                @else
                    <ol class="space-y-4">
                        @foreach ($complaint->publicReplies as $reply)
                            <li class="card {{ $reply->isFromCompany() ? 'ring-brand-200' : '' }}">
                                <div class="card-body">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($reply->isFromCompany())
                                            <span class="badge bg-brand-50 text-brand-700 ring-brand-200">Resposta da empresa</span>
                                        @else
                                            <span class="badge bg-ink-100 text-ink-700 ring-ink-200">Consumidor</span>
                                        @endif

                                        @if ($reply->is_resolution_proposal)
                                            <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200">Proposta de solução</span>
                                        @endif

                                        <span class="ml-auto text-xs text-ink-400">
                                            <time datetime="{{ $reply->published_at?->toIso8601String() }}">
                                                {{ $reply->published_at?->translatedFormat('j M Y, H:i') }}
                                            </time>
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm font-semibold text-ink-800">{{ $reply->displayName() }}</p>
                                    <div class="prose-qm mt-2 whitespace-pre-line text-sm">{{ $reply->body }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            {{-- Avaliação final --}}
            @if ($complaint->rating)
                <section class="card mt-6" aria-labelledby="avaliacao">
                    <div class="card-body">
                        <h2 id="avaliacao" class="text-lg font-semibold">Avaliação do consumidor</h2>
                        <div class="mt-3 flex flex-wrap items-center gap-4">
                            <x-stars :rating="$complaint->rating" show-value />
                            @if ($complaint->would_recommend !== null)
                                <span class="text-sm text-ink-600">
                                    {{ $complaint->would_recommend ? 'Voltaria a comprar nesta empresa' : 'Não voltaria a comprar nesta empresa' }}
                                </span>
                            @endif
                        </div>
                        @if ($complaint->rating_comment)
                            <p class="mt-3 text-sm italic text-ink-600">&ldquo;{{ $complaint->rating_comment }}&rdquo;</p>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Timeline --}}
            <section class="card mt-6" aria-labelledby="historico">
                <div class="card-body">
                    <h2 id="historico" class="text-lg font-semibold">Histórico</h2>
                    <ol class="mt-4 space-y-0">
                        @foreach ($complaint->publicEvents as $event)
                            <li class="relative flex gap-4 pb-6 last:pb-0">
                                @unless ($loop->last)
                                    <span class="absolute left-[11px] top-6 h-full w-px bg-ink-200" aria-hidden="true"></span>
                                @endunless
                                <span class="relative z-10 mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full
                                             {{ in_array($event->type->icon(), ['check'], true) ? 'bg-emerald-100 text-emerald-700' : 'bg-ink-100 text-ink-500' }}">
                                    <span class="size-2 rounded-full bg-current"></span>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-ink-800">{{ $event->type->label() }}</p>
                                    @if ($event->summary)
                                        <p class="mt-0.5 text-sm text-ink-600">{{ $event->summary }}</p>
                                    @endif
                                    <p class="mt-0.5 text-xs text-ink-400">
                                        <time datetime="{{ $event->created_at?->toIso8601String() }}">
                                            {{ $event->created_at?->translatedFormat('j M Y, H:i') }}
                                        </time>
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            {{-- Denúncia --}}
            <p class="mt-6 text-center text-xs text-ink-400">
                Vês algo que não devia estar aqui?
                <a href="{{ route('contact') }}?assunto=denuncia-{{ $complaint->reference }}" class="underline underline-offset-2 hover:text-ink-600">
                    Denuncia este conteúdo
                </a>
            </p>
        </article>

        {{-- Barra lateral --}}
        <aside class="mt-8 lg:mt-0">
            <div class="lg:sticky lg:top-24 space-y-6">
                @if ($complaint->company)
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-center gap-3">
                                <x-company-avatar :company="$complaint->company" size="lg" />
                                <div class="min-w-0">
                                    <h2 class="truncate font-semibold">
                                        <a href="{{ $complaint->company->url() }}" class="hover:text-brand-700">{{ $complaint->company->name }}</a>
                                    </h2>
                                    <p class="truncate text-xs text-ink-500">{{ $complaint->company->category?->name }}</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <x-index-badge :company="$complaint->company" size="lg" />
                            </div>

                            <dl class="mt-4 space-y-2 border-t border-ink-100 pt-4 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-ink-500">Reclamações</dt>
                                    <dd class="font-semibold">{{ number_format($complaint->company->published_complaints_count, 0, ',', ' ') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-ink-500">Taxa de resposta</dt>
                                    <dd class="font-semibold">{{ $complaint->company->response_rate !== null ? number_format($complaint->company->response_rate, 0).'%' : '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-ink-500">Taxa de resolução</dt>
                                    <dd class="font-semibold">{{ $complaint->company->resolution_rate !== null ? number_format($complaint->company->resolution_rate, 0).'%' : '—' }}</dd>
                                </div>
                            </dl>

                            <a href="{{ route('companies.complaints', $complaint->company->slug) }}" class="btn btn-secondary mt-4 w-full">
                                Ver todas as reclamações
                            </a>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-sm text-ink-600">Passaste pelo mesmo?</p>
                        <a href="{{ route('complaints.create', ['empresa' => $complaint->company?->name]) }}" class="btn btn-primary mt-3 w-full">
                            Fazer a minha reclamação
                        </a>
                    </div>
                </div>

                @if ($related->isNotEmpty())
                    <div>
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500">Outras reclamações</h2>
                        <ul class="space-y-3">
                            @foreach ($related as $item)
                                <li class="card">
                                    <a href="{{ $item->url() }}" class="block p-4 hover:bg-ink-50">
                                        <p class="line-clamp-2 text-sm font-medium text-ink-800">{{ $item->title }}</p>
                                        <p class="mt-1.5 text-xs text-ink-400">{{ $item->published_at?->translatedFormat('j M Y') }}</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>
@endsection
