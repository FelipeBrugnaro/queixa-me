@extends('layouts.app', ['hideBreadcrumbs' => true])

@php
    /**
     * Moldura das áreas autenticadas (consumidor, empresa, administração).
     *
     * A navegação é injetada pelo PanelNavigationComposer, não repetida em
     * cada vista. A classe `panel-ui` devolve os títulos à sans: em ecrãs
     * densos de dados a serifa competiria com os números.
     */
    $panelTitle ??= 'A minha área';
    $panelNav ??= [];
@endphp

@section('content')
<div class="panel-ui container-page py-10">
    <div class="lg:grid lg:grid-cols-[15rem_1fr] lg:gap-12">

        <aside class="mb-8 lg:mb-0">
            <div class="lg:sticky lg:top-28">
                <p class="eyebrow mb-4 hidden lg:block">{{ $panelTitle }}</p>

                <nav aria-label="{{ $panelTitle }}"
                     class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-2 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0 lg:pb-0">
                    @foreach ($panelNav as $item)
                        @php $isActive = $item['active'] ?? request()->routeIs($item['route'] ?? '__none__'); @endphp
                        <a href="{{ $item['url'] ?? route($item['route']) }}"
                           class="flex shrink-0 items-center justify-between gap-3 rounded-md px-3 py-2 text-sm transition
                                  {{ $isActive
                                        ? 'bg-ink-900 font-semibold text-white'
                                        : 'font-medium text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}"
                           @if($isActive) aria-current="page" @endif>
                            <span>{{ $item['label'] }}</span>
                            @if (! empty($item['badge']))
                                <span class="rounded px-1.5 py-0.5 text-[0.6875rem] font-semibold tabular-nums
                                             {{ $isActive ? 'bg-white/15 text-white' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        <div class="min-w-0">
            @hasSection('panel-heading')
                <div class="mb-8 border-b border-ink-200 pb-6">@yield('panel-heading')</div>
            @endif

            @yield('panel')
        </div>
    </div>
</div>
@endsection
