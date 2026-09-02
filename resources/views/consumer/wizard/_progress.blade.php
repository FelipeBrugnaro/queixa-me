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

<div class="mb-10">
    {{-- Passos em pastilhas ligadas: mostra onde se está e quanto falta,
         sem ocupar o espaço de uma barra lateral. --}}
    <ol class="hidden items-center gap-1.5 sm:flex">
        @foreach ($labels as $number => $label)
            <li class="flex flex-1 items-center gap-2">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-full text-[0.6875rem] font-extrabold transition-all
                             {{ $number < $step
                                    ? 'bg-brand-500 text-white'
                                    : ($number === $step
                                        ? 'bg-brand-600 text-white ring-4 ring-brand-500/20'
                                        : 'bg-ink-100 text-ink-400') }}">
                    @if ($number < $step)
                        <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.7 6.3a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4 0l-3-3a1 1 0 1 1 1.4-1.4L9 12.6l6.3-6.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/>
                        </svg>
                    @else
                        {{ $number }}
                    @endif
                </span>

                <span class="hidden text-xs font-bold lg:inline {{ $number <= $step ? 'text-ink-800' : 'text-ink-400' }}">
                    {{ $label }}
                </span>

                @unless ($loop->last)
                    <span class="h-0.5 flex-1 rounded-full {{ $number < $step ? 'bg-brand-400' : 'bg-ink-200' }}" aria-hidden="true"></span>
                @endunless
            </li>
        @endforeach
    </ol>

    {{-- Em ecrãs pequenos, barra simples. --}}
    <div class="sm:hidden">
        <div class="mb-2 flex items-baseline justify-between">
            <p class="text-sm font-extrabold text-ink-900">{{ $labels[$step] }}</p>
            <p class="text-xs font-semibold text-ink-500">{{ $step }} de {{ $total }}</p>
        </div>
        <div class="index-track" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"
             aria-label="Progresso da reclamação">
            <div class="index-fill bg-brand-500" style="width: {{ $percent }}%"></div>
        </div>
    </div>
</div>
