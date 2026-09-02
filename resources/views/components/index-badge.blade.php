@props(['company', 'showLabel' => true, 'size' => 'md'])

@php
    $value = $company->satisfaction_index;
    $enough = $company->hasEnoughDataForIndex();
    $padding = $size === 'lg' ? 'px-3.5 py-2 text-base' : 'px-2.5 py-1 text-xs';
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full font-semibold ring-1 ring-inset {{ $padding }} {{ $company->satisfactionColorClasses() }}"
      @if(! $enough) title="Ainda sem reclamações suficientes para um índice fiável" @endif>
    @if ($value !== null && $enough)
        <span>{{ number_format($value, 0, ',', '') }}</span>
        <span class="font-normal opacity-70">/100</span>
        @if ($showLabel)
            <span class="hidden font-normal opacity-80 sm:inline">· {{ $company->satisfactionLabel() }}</span>
        @endif
    @else
        <span class="font-medium">Dados insuficientes</span>
    @endif
</span>
