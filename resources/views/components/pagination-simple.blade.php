@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginação" class="mt-8 flex items-center justify-between gap-3">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary opacity-50" aria-disabled="true">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary">Anterior</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary">Seguinte</a>
        @else
            <span class="btn btn-secondary opacity-50" aria-disabled="true">Seguinte</span>
        @endif
    </nav>
@endif
