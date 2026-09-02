{{--
    Pesquisa em sobreposição.

    Redirecionar para uma página de pesquisa faz perder o contexto e obriga a
    uma navegação de ida e volta para uma tarefa de dois segundos. Aqui o
    utilizador escreve, vê as empresas a aparecer e salta directamente para a
    ficha — sem sair de onde estava.
--}}
<div id="search-modal" data-search-modal hidden
     class="modal-backdrop flex items-start justify-center p-4 pt-[12vh]"
     role="dialog" aria-modal="true" aria-labelledby="search-modal-title">

    <div class="modal-panel max-w-2xl" data-search-panel>
        <h2 id="search-modal-title" class="sr-only">Pesquisar no queixa.me</h2>

        <form action="{{ route('search') }}" method="GET" role="search">
            <div class="flex items-center gap-3 border-b border-ink-100 px-5 py-4">
                <svg class="size-5 shrink-0 text-ink-400" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3.3 3.3"/>
                </svg>

                <input type="search" name="q" id="search-modal-input"
                       data-search-input
                       data-endpoint="{{ route('companies.suggest') }}"
                       placeholder="Procura uma empresa, marca ou loja…"
                       autocomplete="off"
                       aria-controls="search-modal-results"
                       class="min-w-0 flex-1 border-0 bg-transparent py-1 text-base font-medium text-ink-900 placeholder:text-ink-400 placeholder:font-normal focus:outline-none">

                <kbd class="hidden rounded-md border border-ink-200 bg-ink-50 px-1.5 py-0.5 text-[0.6875rem] font-semibold text-ink-400 sm:block">
                    esc
                </kbd>

                <button type="button" data-search-close
                        class="flex size-8 shrink-0 items-center justify-center rounded-lg text-ink-400 transition hover:bg-ink-100 hover:text-ink-700 sm:hidden"
                        aria-label="Fechar pesquisa">
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="m5 5 10 10M15 5 5 15"/>
                    </svg>
                </button>
            </div>

            {{-- Resultados instantâneos --}}
            <div id="search-modal-results" data-search-results class="max-h-[50vh] overflow-y-auto"></div>

            {{-- Atalhos enquanto não há termo escrito --}}
            <div data-search-empty class="px-5 py-5">
                <p class="eyebrow mb-3">Ir para</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ([
                        ['Ranking de empresas', route('ranking')],
                        ['Reclamações recentes', route('complaints.index')],
                        ['Comparar marcas', route('compare')],
                        ['Marcas do mês', route('awards')],
                    ] as [$label, $url])
                        <a href="{{ $url }}"
                           class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-ink-700 transition hover:bg-ink-50">
                            <span class="flex size-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600" aria-hidden="true">
                                <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 4l6 6-6 6"/>
                                </svg>
                            </span>
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-ink-100 bg-ink-50/60 px-5 py-3">
                <button type="submit" class="text-xs font-bold text-brand-700 transition hover:text-brand-800">
                    Ver todos os resultados <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </form>
    </div>
</div>
