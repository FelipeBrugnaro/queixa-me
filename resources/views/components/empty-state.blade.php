@props(['title', 'description' => null])

<div class="rounded-xl border border-dashed border-ink-300 bg-ink-50/50">
    <div class="flex flex-col items-center px-6 py-16 text-center">
        <h3 class="text-lg text-ink-800">{{ $title }}</h3>
        @if ($description)
            <p class="mt-2 max-w-md text-sm leading-relaxed text-ink-500">{{ $description }}</p>
        @endif
        @if (trim($slot))
            <div class="mt-6">{{ $slot }}</div>
        @endif
    </div>
</div>
