@php
    $labels = [
        1 => 'Empresa',
        2 => 'O que aconteceu',
        3 => 'Detalhes',
        4 => 'Os teus dados',
        5 => 'Confirmar',
    ];
    $total = count($labels);
    $percent = (int) round($step / $total * 100);
@endphp

<div class="mb-8">
    <div class="mb-3 flex items-baseline justify-between gap-4">
        <p class="text-sm font-semibold text-ink-700">
            Passo {{ $step }} de {{ $total }} · {{ $labels[$step] }}
        </p>
        <p class="text-sm text-ink-500">{{ $percent }}%</p>
    </div>

    <div class="h-2 overflow-hidden rounded-full bg-ink-200"
         role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"
         aria-label="Progresso da reclamação">
        <div class="h-full rounded-full bg-brand-600 transition-all duration-300" style="width: {{ $percent }}%"></div>
    </div>

    <ol class="mt-4 hidden gap-1 sm:grid sm:grid-cols-5">
        @foreach ($labels as $number => $label)
            <li class="flex items-center gap-2 text-xs {{ $number <= $step ? 'text-ink-800' : 'text-ink-400' }}">
                <span class="flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold
                             {{ $number < $step ? 'bg-emerald-100 text-emerald-700' : ($number === $step ? 'bg-brand-600 text-white' : 'bg-ink-200 text-ink-500') }}">
                    @if ($number < $step)
                        <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.7 6.3a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4 0l-3-3a1 1 0 1 1 1.4-1.4L9 12.6l6.3-6.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/>
                        </svg>
                    @else
                        {{ $number }}
                    @endif
                </span>
                <span class="truncate">{{ $label }}</span>
            </li>
        @endforeach
    </ol>
</div>
