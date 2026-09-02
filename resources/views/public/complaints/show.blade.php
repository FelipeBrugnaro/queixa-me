@extends('layouts.app')

@section('content')
<div class="container-page py-10">
    <div class="lg:grid lg:grid-cols-12 lg:gap-12">

        <article class="min-w-0 lg:col-span-8">

            {{-- Cabeçalho editorial: título em serifa, metadados em fio --}}
            <header class="border-b border-ink-200 pb-8">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge {{ $complaint->stage->badgeClasses() }}">{{ $complaint->stage->label() }}</span>
                    @if ($complaint->category)
                        <span class="badge bg-ink-100 text-ink-700 ring-ink-200">{{ $complaint->category->name }}</span>
                    @endif
                    <span class="text-xs tabular-nums text-ink-400">{{ $complaint->reference }}</span>
                </div>

                <h1 class="mt-5 text-3xl leading-[1.1] sm:text-[2.75rem]">{{ $complaint->title }}</h1>

                <div class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-ink-500">
                    <span>Por <strong class="font-medium text-ink-800">{{ $complaint->authorDisplayName() }}</strong></span>
                    <span class="text-ink-300" aria-hidden="true">/</span>
                    <time datetime="{{ $complaint->published_at?->toDateString() }}">
                        {{ $complaint->published_at?->translatedFormat('j \d\e F \d\e Y') }}
                    </time>
                    @if ($complaint->occurred_on)
                        <span class="text-ink-300" aria-hidden="true">/</span>
                        <span>ocorrência a {{ $complaint->occurred_on->translatedFormat('j/m/Y') }}</span>
                    @endif
                    @if ($complaint->district)
                        <span class="text-ink-300" aria-hidden="true">/</span>
                        <span>{{ $complaint->district }}</span>
                    @endif
                </div>
            </header>

            @if ($complaint->awaitsCompanyReply() && $complaint->responseSlaBreached())
                <p class="mt-6 border-l-2 border-amber-400 bg-amber-50/60 py-3 pl-4 text-sm text-amber-900">
                    Esta reclamação está sem resposta da empresa há
                    <strong class="font-semibold">{{ $complaint->daysWaitingForReply() }} dias</strong>.
                </p>
            @endif

            {{-- Relato --}}
            <section class="mt-10" aria-labelledby="descricao">
                <h2 id="descricao" class="eyebrow">O que aconteceu</h2>
                <div class="prose-qm mt-4 whitespace-pre-line">{{ $complaint->description }}</div>

                @if ($complaint->desired_resolution)
                    <div class="mt-8 border-l-2 border-brand-400 bg-brand-50/50 py-4 pl-5 pr-4">
                        <h3 class="eyebrow text-brand-800">Resolução pretendida</h3>
                        <p class="mt-2 whitespace-pre-line text-[0.9375rem] leading-relaxed text-ink-800">
                            {{ $complaint->desired_resolution }}
                        </p>
                    </div>
                @endif

                @if ($complaint->publicAttachments->isNotEmpty())
                    <div class="mt-8">
                        <h3 class="eyebrow">Anexos públicos</h3>
                        <ul class="mt-3 flex flex-wrap gap-2">
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
            </section>

            {{-- Fio de respostas --}}
            <section class="mt-14" aria-labelledby="respostas">
                <div class="rule-heading">
                    <h2 id="respostas" class="text-2xl">Respostas</h2>
                    <span class="text-sm tabular-nums text-ink-400">{{ $complaint->publicReplies->count() }}</span>
                </div>

                @if ($complaint->publicReplies->isEmpty())
                    <p class="mt-6 text-sm text-ink-500">
                        A empresa ainda não respondeu publicamente a esta reclamação.
                    </p>
                @else
                    <ol class="mt-6 space-y-5">
                        @foreach ($complaint->publicReplies as $reply)
                            {{-- Respostas da empresa recebem fundo e barra: quem
                                 lê tem de distinguir imediatamente as duas vozes. --}}
                            <li class="relative overflow-hidden rounded-xl border {{ $reply->isFromCompany() ? 'border-brand-200 bg-brand-50/40' : 'border-ink-200 bg-surface' }}">
                                <span class="absolute inset-y-0 left-0 w-[3px] {{ $reply->isFromCompany() ? 'bg-brand-500' : 'bg-ink-300' }}" aria-hidden="true"></span>

                                <div class="py-5 pl-6 pr-5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-ink-900">{{ $reply->displayName() }}</span>

                                        @if ($reply->isFromCompany())
                                            <span class="badge bg-brand-100 text-brand-800 ring-brand-200">Empresa</span>
                                        @endif

                                        @if ($reply->is_resolution_proposal)
                                            <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200">Proposta de solução</span>
                                        @endif

                                        <time class="ml-auto text-xs text-ink-400" datetime="{{ $reply->published_at?->toIso8601String() }}">
                                            {{ $reply->published_at?->translatedFormat('j M Y, H:i') }}
                                        </time>
                                    </div>

                                    <div class="prose-qm mt-3 whitespace-pre-line text-sm">{{ $reply->body }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            {{-- Avaliação --}}
            @if ($complaint->rating)
                <section class="mt-14" aria-labelledby="avaliacao">
                    <div class="rule-heading">
                        <h2 id="avaliacao" class="text-2xl">Avaliação do consumidor</h2>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3">
                        <div class="flex items-baseline gap-2">
                            <span class="font-display text-4xl leading-none text-ink-900">{{ $complaint->rating }}</span>
                            <span class="text-sm text-ink-400">/5</span>
                        </div>
                        <x-stars :rating="$complaint->rating" />
                        @if ($complaint->would_recommend !== null)
                            <span class="text-sm text-ink-600">
                                {{ $complaint->would_recommend ? 'Voltaria a comprar nesta empresa' : 'Não voltaria a comprar nesta empresa' }}
                            </span>
                        @endif
                    </div>

                    @if ($complaint->rating_comment)
                        <blockquote class="font-display mt-5 border-l-2 border-ink-300 pl-5 text-xl italic leading-snug text-ink-700">
                            {{ $complaint->rating_comment }}
                        </blockquote>
                    @endif
                </section>
            @endif

            {{-- Histórico --}}
            <section class="mt-14" aria-labelledby="historico">
                <div class="rule-heading">
                    <h2 id="historico" class="text-2xl">Histórico</h2>
                </div>

                <ol class="mt-6">
                    @foreach ($complaint->publicEvents as $event)
                        <li class="relative flex gap-5 pb-7 last:pb-0">
                            @unless ($loop->last)
                                <span class="absolute left-[7px] top-4 h-full w-px bg-ink-200" aria-hidden="true"></span>
                            @endunless

                            <span class="relative z-10 mt-1.5 flex size-[15px] shrink-0 items-center justify-center rounded-full border-2 border-paper
                                         {{ $event->type->icon() === 'check' ? 'bg-brand-500' : 'bg-ink-300' }}"></span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-ink-900">{{ $event->type->label() }}</p>
                                @if ($event->summary)
                                    <p class="mt-0.5 text-sm text-ink-600">{{ $event->summary }}</p>
                                @endif
                                <time class="mt-1 block text-xs text-ink-400" datetime="{{ $event->created_at?->toIso8601String() }}">
                                    {{ $event->created_at?->translatedFormat('j M Y, H:i') }}
                                </time>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <p class="mt-14 border-t border-ink-200 pt-6 text-xs text-ink-400">
                Vês algo que não devia estar aqui?
                <a href="{{ route('contact') }}?assunto=denuncia-{{ $complaint->reference }}"
                   class="underline decoration-ink-300 underline-offset-2 transition hover:text-ink-700">
                    Denuncia este conteúdo
                </a>
            </p>
        </article>

        {{-- Coluna lateral --}}
        <aside class="mt-12 lg:col-span-4 lg:mt-0">
            <div class="space-y-8 lg:sticky lg:top-28">

                @if ($complaint->company)
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-center gap-3">
                                <x-company-avatar :company="$complaint->company" size="lg" />
                                <div class="min-w-0">
                                    <h2 class="truncate text-base" style="font-family: var(--font-sans); font-weight: 600">
                                        <a href="{{ $complaint->company->url() }}" class="transition hover:text-brand-800">
                                            {{ $complaint->company->name }}
                                        </a>
                                    </h2>
                                    <p class="truncate text-xs text-ink-500">{{ $complaint->company->category?->name }}</p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-index-badge :company="$complaint->company" size="lg" />
                            </div>

                            <dl class="mt-6 space-y-3 border-t border-ink-100 pt-5 text-sm">
                                @foreach ([
                                    ['Reclamações', number_format($complaint->company->published_complaints_count, 0, ',', ' ')],
                                    ['Taxa de resposta', $complaint->company->response_rate !== null ? number_format($complaint->company->response_rate, 0, ',', '').'%' : '—'],
                                    ['Taxa de resolução', $complaint->company->resolution_rate !== null ? number_format($complaint->company->resolution_rate, 0, ',', '').'%' : '—'],
                                ] as [$label, $value])
                                    <div class="flex items-baseline justify-between gap-3">
                                        <dt class="text-ink-500">{{ $label }}</dt>
                                        <dd class="font-semibold text-ink-900">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>

                            <a href="{{ route('companies.complaints', $complaint->company->slug) }}" class="btn btn-secondary mt-5 w-full">
                                Ver todas as reclamações
                            </a>
                        </div>
                    </div>
                @endif

                <div class="border-l-2 border-brand-500 pl-5">
                    <p class="font-display text-xl leading-snug text-ink-900">Passaste pelo mesmo?</p>
                    <p class="mt-2 text-sm leading-relaxed text-ink-600">
                        Cada reclamação publicada aumenta a probabilidade de a empresa responder.
                    </p>
                    <a href="{{ route('complaints.create', ['empresa' => $complaint->company?->name]) }}" class="btn btn-primary mt-4">
                        Fazer a minha reclamação
                    </a>
                </div>

                @if ($related->isNotEmpty())
                    <div>
                        <h2 class="eyebrow border-b border-ink-200 pb-3">Outras reclamações</h2>
                        <ul>
                            @foreach ($related as $item)
                                <li class="border-b border-ink-100">
                                    <a href="{{ $item->url() }}" class="group block py-3.5">
                                        <p class="line-clamp-2 text-sm leading-snug text-ink-800 transition group-hover:text-brand-700">
                                            {{ $item->title }}
                                        </p>
                                        <p class="mt-1 text-xs text-ink-400">{{ $item->published_at?->translatedFormat('j M Y') }}</p>
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
