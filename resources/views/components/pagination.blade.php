@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginação" class="mt-10 flex items-center justify-between gap-4 border-t border-ink-200 pt-6">
        <div class="hidden text-sm text-ink-500 sm:block">
            {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} de {{ number_format($paginator->total(), 0, ',', ' ') }}
        </div>

        <div class="flex flex-1 items-center justify-between gap-2 sm:flex-none">
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary opacity-50" aria-disabled="true">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary">Anterior</a>
            @endif

            <ol class="hidden items-center gap-1 md:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="px-2 text-ink-400">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li>
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="flex size-9 items-center justify-center rounded-lg bg-brand-600 text-sm font-semibold text-white">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="flex size-9 items-center justify-center rounded-lg text-sm font-medium text-ink-600 hover:bg-ink-100">{{ $page }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach
            </ol>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary">Seguinte</a>
            @else
                <span class="btn btn-secondary opacity-50" aria-disabled="true">Seguinte</span>
            @endif
        </div>
    </nav>
@endif
