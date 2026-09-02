@php
    /*
     * Avisos com barra lateral em vez de caixa colorida cheia: chamam a
     * atenção sem gritar, e mantêm a mesma linguagem visual dos restantes
     * destaques do portal.
     */
    $messages = [
        'success' => 'border-brand-500 bg-brand-50/60 text-brand-900',
        'error' => 'border-rose-500 bg-rose-50/60 text-rose-900',
        'warning' => 'border-amber-500 bg-amber-50/60 text-amber-900',
        'info' => 'border-indigo-400 bg-indigo-50/60 text-indigo-900',
    ];
@endphp

@foreach ($messages as $key => $classes)
    @if (session($key))
        <div class="container-page pt-6" role="status" aria-live="polite">
            <p class="border-l-2 py-3 pl-4 pr-4 text-sm {{ $classes }}">{{ session($key) }}</p>
        </div>
    @endif
@endforeach

@if ($errors->any() && $errors->count() > 1)
    <div class="container-page pt-6">
        <div class="border-l-2 border-rose-500 bg-rose-50/60 py-3 pl-4 pr-4 text-sm text-rose-900">
            <p class="font-semibold">Corrige os seguintes pontos antes de continuar:</p>
            <ul class="mt-2 ml-4 list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
