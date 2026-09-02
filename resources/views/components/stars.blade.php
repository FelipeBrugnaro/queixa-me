@props(['rating', 'showValue' => false])

@php $rating = (int) $rating; @endphp

<span class="inline-flex items-center gap-1" role="img" aria-label="Avaliação: {{ $rating }} em 5">
    <span class="flex items-center gap-px">
        @for ($i = 1; $i <= 5; $i++)
            <svg class="size-3 {{ $i <= $rating ? 'text-amber-500' : 'text-ink-200' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10 1.5l2.6 5.3 5.9.85-4.25 4.15 1 5.85L10 14.9l-5.25 2.75 1-5.85L1.5 7.65l5.9-.85L10 1.5Z"/>
            </svg>
        @endfor
    </span>
    @if ($showValue)
        <span class="text-xs font-semibold text-ink-700">{{ $rating }}/5</span>
    @endif
</span>
