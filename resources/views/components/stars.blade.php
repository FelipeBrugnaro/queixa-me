@props(['rating', 'showValue' => false])

@php $rating = (int) $rating; @endphp

<span class="inline-flex items-center gap-0.5" role="img" aria-label="Avaliação: {{ $rating }} em 5">
    @for ($i = 1; $i <= 5; $i++)
        <svg class="size-3.5 {{ $i <= $rating ? 'text-amber-400' : 'text-ink-200' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 1.5l2.6 5.3 5.9.85-4.25 4.15 1 5.85L10 14.9l-5.25 2.75 1-5.85L1.5 7.65l5.9-.85L10 1.5Z"/>
        </svg>
    @endfor
    @if ($showValue)
        <span class="ml-1 text-xs font-medium text-ink-600">{{ $rating }}/5</span>
    @endif
</span>
