@props(['company', 'showLabel' => true, 'size' => 'md'])

@php
    /*
     * Elemento de assinatura do portal.
     *
     * O índice é o número que o utilizador vem comparar, por isso tem
     * tratamento tipográfico próprio — serifa, algarismos tabulares, escala
     * visível — em vez de ser mais uma pastilha colorida entre outras.
     */
    $value = $company->satisfaction_index;
    $enough = $company->hasEnoughDataForIndex();
    $show = $value !== null && $enough;
    $width = $show ? max(2, min(100, (int) round($value))) : 0;
@endphp

@if ($size === 'lg')
    <div class="w-full">
        <div class="flex items-baseline gap-2">
            @if ($show)
                <span class="font-display text-4xl leading-none text-ink-900">{{ number_format($value, 0, ',', '') }}</span>
                <span class="text-sm font-medium text-ink-400">/100</span>
            @else
                <span class="font-display text-2xl leading-none text-ink-400">—</span>
            @endif
        </div>

        <div class="index-track mt-2.5">
            <div class="index-fill {{ $company->satisfactionBarClass() }}" style="width: {{ $width }}%"></div>
        </div>

        <p class="mt-2 text-xs font-medium {{ $show ? 'text-ink-600' : 'text-ink-400' }}">
            {{ $show ? 'Índice de satisfação · '.$company->satisfactionLabel() : 'Dados insuficientes para um índice fiável' }}
        </p>
    </div>
@else
    <span class="index-chip text-sm ring-1 ring-inset {{ $company->satisfactionColorClasses() }}"
          @if (! $show) title="Ainda sem reclamações suficientes para um índice fiável" @endif>
        @if ($show)
            <span class="font-display text-base leading-none">{{ number_format($value, 0, ',', '') }}</span>
            <span class="text-[0.6875rem] font-medium opacity-60">/100</span>
            @if ($showLabel)
                <span class="hidden text-[0.6875rem] font-medium opacity-75 sm:inline">
                    · {{ $company->satisfactionLabel() }}
                </span>
            @endif
        @else
            <span class="text-[0.6875rem] font-medium">Sem dados</span>
        @endif
    </span>
@endif
