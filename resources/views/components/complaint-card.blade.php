@props(['complaint', 'showCompany' => true, 'compact' => false])

<article class="card card-hover overflow-hidden">
    <div class="card-body">
        <div class="flex items-start gap-4">
            @if ($showCompany && $complaint->company)
                <a href="{{ $complaint->company->url() }}" class="shrink-0" aria-label="Ver ficha de {{ $complaint->company->name }}">
                    <x-company-avatar :company="$complaint->company" size="md" />
                </a>
            @endif

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-ink-500">
                    @if ($showCompany && $complaint->company)
                        <a href="{{ $complaint->company->url() }}" class="font-semibold text-ink-700 hover:text-brand-700">
                            {{ $complaint->company->name }}
                        </a>
                        <span aria-hidden="true">·</span>
                    @endif

                    @if ($complaint->category)
                        <span>{{ $complaint->category->name }}</span>
                        <span aria-hidden="true">·</span>
                    @endif

                    <time datetime="{{ $complaint->published_at?->toDateString() }}">
                        {{ $complaint->published_at?->translatedFormat('j M Y') }}
                    </time>
                </div>

                <h3 class="mt-1.5 text-base font-semibold leading-snug">
                    <a href="{{ $complaint->url() }}" class="hover:text-brand-700">
                        {{ $complaint->title }}
                    </a>
                </h3>

                @unless ($compact)
                    <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-600">
                        {{ $complaint->excerpt(200) }}
                    </p>
                @endunless

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="badge {{ $complaint->stage->badgeClasses() }}">
                        {{ $complaint->stage->label() }}
                    </span>

                    @if ($complaint->stage->hasCompanyReply())
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-ink-500">
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M3.5 2.75A1.75 1.75 0 0 0 1.75 4.5v8.5c0 .97.78 1.75 1.75 1.75H5v2.4a.6.6 0 0 0 .98.47L9.9 14.75h6.6a1.75 1.75 0 0 0 1.75-1.75V4.5a1.75 1.75 0 0 0-1.75-1.75h-13Z"/>
                            </svg>
                            {{ $complaint->replies_count }} {{ $complaint->replies_count === 1 ? 'resposta' : 'respostas' }}
                        </span>
                    @elseif ($complaint->responseSlaBreached())
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700">
                            Sem resposta há {{ $complaint->daysWaitingForReply() }} dias
                        </span>
                    @endif

                    @if ($complaint->rating)
                        <x-stars :rating="$complaint->rating" />
                    @endif

                    <span class="ml-auto text-xs text-ink-400">{{ $complaint->authorDisplayName() }}</span>
                </div>
            </div>
        </div>
    </div>
</article>
