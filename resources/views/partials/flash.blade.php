@php
    $messages = [
        'success' => ['bg' => 'bg-emerald-50 text-emerald-900 ring-emerald-200', 'icon' => 'M16.7 6.3a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4 0l-3-3a1 1 0 1 1 1.4-1.4L9 12.6l6.3-6.3a1 1 0 0 1 1.4 0Z'],
        'error' => ['bg' => 'bg-rose-50 text-rose-900 ring-rose-200', 'icon' => 'M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm0 4a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0V7a1 1 0 0 1 1-1Zm0 8.5a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4Z'],
        'warning' => ['bg' => 'bg-amber-50 text-amber-900 ring-amber-200', 'icon' => 'M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm0 4a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0V7a1 1 0 0 1 1-1Zm0 8.5a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4Z'],
        'info' => ['bg' => 'bg-brand-50 text-brand-900 ring-brand-200', 'icon' => 'M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm1 5.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9h2v5H9V9Z'],
    ];
@endphp

@foreach ($messages as $key => $style)
    @if (session($key))
        <div class="container-page pt-5" role="status" aria-live="polite">
            <div class="flex items-start gap-3 rounded-xl px-4 py-3 text-sm ring-1 ring-inset {{ $style['bg'] }}">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="{{ $style['icon'] }}" clip-rule="evenodd"/>
                </svg>
                <div class="min-w-0 flex-1">{{ session($key) }}</div>
            </div>
        </div>
    @endif
@endforeach

@if ($errors->any() && $errors->count() > 1)
    <div class="container-page pt-5">
        <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-900 ring-1 ring-inset ring-rose-200">
            <p class="font-semibold">Corrige os seguintes pontos antes de continuar:</p>
            <ul class="mt-2 ml-4 list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
