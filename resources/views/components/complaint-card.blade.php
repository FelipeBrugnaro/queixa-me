@props(['complaint', 'showCompany' => true, 'compact' => false])

@php
    /*
     * O cartão de reclamação foi desenhado à volta da pergunta que o leitor
     * traz: "isto acabou bem?". Por isso o desfecho é a informação com mais
     * peso visual — uma barra de cor à esquerda que se lê num relance, antes
     * mesmo do título.
     */
    $accent = match (true) {
        $complaint->stage->value === 'resolved' => 'bg-brand-500',
        $complaint->stage->value === 'unresolved' => 'bg-rose-400',
        $complaint->stage->hasCompanyReply() => 'bg-indigo-400',
        $complaint->responseSlaBreached() => 'bg-amber-400',
        default => 'bg-ink-200',
    };
@endphp

<article class="card card-hover group relative overflow-hidden">
    <span class="absolute inset-y-0 left-0 w-[3px] {{ $accent }}" aria-hidden="true"></span>

    <div class="py-5 pl-6 pr-5 sm:pl-7 sm:pr-6">
        {{-- Linha de contexto --}}
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-ink-500">
            @if ($showCompany && $complaint->company)
                <a href="{{ $complaint->company->url() }}"
                   class="font-semibold text-ink-800 transition hover:text-brand-700">
                    {{ $complaint->company->name }}
                </a>
                <span class="text-ink-300" aria-hidden="true">/</span>
            @endif

            @if ($complaint->category)
                <span>{{ $complaint->category->name }}</span>
                <span class="text-ink-300" aria-hidden="true">/</span>
            @endif

            <time datetime="{{ $complaint->published_at?->toDateString() }}">
                {{ $complaint->published_at?->translatedFormat('j M Y') }}
            </time>
        </div>

        {{-- Título --}}
        <h3 class="mt-2 text-lg leading-snug">
            <a href="{{ $complaint->url() }}" class="transition hover:text-brand-800">
                <span class="absolute inset-0" aria-hidden="true"></span>
                {{ $complaint->title }}
            </a>
        </h3>

        @unless ($compact)
            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-600">
                {{ $complaint->excerpt(190) }}
            </p>
        @endunless

        {{-- Desfecho --}}
        <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2">
            <span class="badge {{ $complaint->stage->badgeClasses() }}">
                {{ $complaint->stage->label() }}
            </span>

            @if ($complaint->stage->hasCompanyReply())
                <span class="text-xs text-ink-500">
                    {{ $complaint->replies_count }} {{ $complaint->replies_count === 1 ? 'resposta' : 'respostas' }}
                </span>
            @elseif ($complaint->responseSlaBreached())
                <span class="text-xs font-medium text-amber-800">
                    {{ $complaint->daysWaitingForReply() }} dias sem resposta
                </span>
            @endif

            @if ($complaint->rating)
                <x-stars :rating="$complaint->rating" />
            @endif

            <span class="ml-auto text-xs text-ink-400">{{ $complaint->authorDisplayName() }}</span>
        </div>
    </div>
</article>
