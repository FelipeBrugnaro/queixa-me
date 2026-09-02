@props(['company', 'size' => 'md'])

@php
    $classes = [
        'sm' => 'size-8 text-[11px] rounded-lg',
        'md' => 'size-11 text-sm rounded-xl',
        'lg' => 'size-16 text-lg rounded-2xl',
        'xl' => 'size-20 text-2xl rounded-2xl',
    ][$size];
@endphp

@if ($company?->logoUrl())
    <img src="{{ $company->logoUrl() }}" alt="Logótipo de {{ $company->name }}" loading="lazy" decoding="async"
         class="{{ $classes }} shrink-0 object-contain bg-white ring-1 ring-ink-200 p-1">
@else
    <span aria-hidden="true"
          class="{{ $classes }} flex shrink-0 items-center justify-center bg-brand-50 font-bold text-brand-700 ring-1 ring-brand-100">
        {{ $company?->initials() ?? '?' }}
    </span>
@endif
