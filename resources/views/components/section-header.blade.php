@props(['title', 'description' => null, 'href' => null, 'linkLabel' => 'Ver tudo', 'eyebrow' => null])

<div class="mb-8">
    @if ($eyebrow)
        <p class="eyebrow mb-2">{{ $eyebrow }}</p>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2 border-b border-ink-200 pb-4">
        <h2 class="text-2xl sm:text-[1.75rem]">{{ $title }}</h2>

        @if ($href)
            <a href="{{ $href }}" class="shrink-0 pb-1 text-sm font-semibold text-brand-700 transition hover:text-brand-900">
                {{ $linkLabel }} <span aria-hidden="true">&rarr;</span>
            </a>
        @endif
    </div>

    @if ($description)
        <p class="mt-4 max-w-2xl text-[0.9375rem] leading-relaxed text-ink-600">{{ $description }}</p>
    @endif
</div>
