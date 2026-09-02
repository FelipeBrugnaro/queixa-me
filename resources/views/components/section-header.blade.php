@props(['title', 'description' => null, 'href' => null, 'linkLabel' => 'Ver tudo', 'level' => 'h2'])

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div class="max-w-2xl">
        <{{ $level }} class="text-xl font-semibold sm:text-2xl">{{ $title }}</{{ $level }}>
        @if ($description)
            <p class="mt-1.5 text-sm leading-relaxed text-ink-600">{{ $description }}</p>
        @endif
    </div>
    @if ($href)
        <a href="{{ $href }}" class="shrink-0 text-sm font-semibold text-brand-700 hover:text-brand-800">
            {{ $linkLabel }} <span aria-hidden="true">&rarr;</span>
        </a>
    @endif
</div>
