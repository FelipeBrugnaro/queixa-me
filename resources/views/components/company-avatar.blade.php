@props(['company', 'size' => 'md'])

@php
    $classes = [
        'sm' => 'size-8 text-[10px] rounded-md',
        'md' => 'size-10 text-xs rounded-lg',
        'lg' => 'size-14 text-base rounded-lg',
        'xl' => 'size-18 text-xl rounded-xl',
    ][$size];
@endphp

@if ($company?->logoUrl())
    <img src="{{ $company->logoUrl() }}" alt="Logótipo de {{ $company->name }}" loading="lazy" decoding="async"
         class="{{ $classes }} shrink-0 border border-ink-200 bg-white object-contain p-1">
@else
    <span aria-hidden="true"
          class="{{ $classes }} flex shrink-0 items-center justify-center border border-ink-200 bg-ink-50 font-semibold tracking-wide text-ink-600">
        {{ $company?->initials() ?? '—' }}
    </span>
@endif
