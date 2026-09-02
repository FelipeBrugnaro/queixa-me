@props(['title', 'description' => null, 'icon' => 'search'])

<div class="card">
    <div class="flex flex-col items-center px-6 py-14 text-center">
        <span class="flex size-12 items-center justify-center rounded-2xl bg-ink-100 text-ink-400" aria-hidden="true">
            <svg class="size-6" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.4 9.83l3.63 3.64a.75.75 0 1 0 1.06-1.06l-3.63-3.64A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd"/>
            </svg>
        </span>
        <h3 class="mt-4 text-base font-semibold">{{ $title }}</h3>
        @if ($description)
            <p class="mt-1.5 max-w-md text-sm text-ink-500">{{ $description }}</p>
        @endif
        @if (trim($slot))
            <div class="mt-5">{{ $slot }}</div>
        @endif
    </div>
</div>
