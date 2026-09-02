@extends('layouts.app', ['hideBreadcrumbs' => true])

@php
    /**
     * Layout das áreas autenticadas (consumidor, empresa, administração).
     * A navegação lateral é passada pelo view que estende este layout,
     * mantendo uma só estrutura para os três painéis.
     */
    $panelTitle ??= 'A minha área';
    $panelNav ??= [];
@endphp

@section('content')
<div class="container-page py-8">
    <div class="lg:grid lg:grid-cols-[16rem_1fr] lg:gap-10">

        {{-- Navegação lateral --}}
        <aside class="mb-6 lg:mb-0">
            <div class="lg:sticky lg:top-24">
                @isset($panelHeader)
                    <div class="mb-5">{{ $panelHeader }}</div>
                @endisset

                <nav aria-label="{{ $panelTitle }}" class="flex gap-1 overflow-x-auto pb-2 lg:flex-col lg:overflow-visible lg:pb-0">
                    @foreach ($panelNav as $item)
                        @php $isActive = $item['active'] ?? request()->routeIs($item['route'] ?? '__none__'); @endphp
                        <a href="{{ $item['url'] ?? route($item['route']) }}"
                           class="flex shrink-0 items-center justify-between gap-2 rounded-xl px-3.5 py-2.5 text-sm font-medium transition
                                  {{ $isActive ? 'bg-brand-600 text-white' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}"
                           @if($isActive) aria-current="page" @endif>
                            <span>{{ $item['label'] }}</span>
                            @if (! empty($item['badge']))
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                                             {{ $isActive ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        {{-- Conteúdo --}}
        <div class="min-w-0">
            @hasSection('panel-heading')
                <div class="mb-6">@yield('panel-heading')</div>
            @endif

            @yield('panel')
        </div>
    </div>
</div>
@endsection
