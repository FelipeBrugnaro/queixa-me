@php
    $user = auth()->user();
    $nav = [
        ['label' => 'Reclamações', 'route' => 'complaints.index', 'pattern' => 'reclamacoes*'],
        ['label' => 'Empresas', 'route' => 'companies.index', 'pattern' => 'empresas*'],
        ['label' => 'Ranking', 'route' => 'ranking', 'pattern' => 'ranking*'],
        ['label' => 'Comparar', 'route' => 'compare', 'pattern' => 'comparar*'],
        ['label' => 'Marcas do mês', 'route' => 'awards', 'pattern' => 'marcas-do-mes*'],
        ['label' => 'Notícias', 'route' => 'blog.index', 'pattern' => 'noticias*'],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-ink-200/70 bg-surface/90 backdrop-blur-lg">
    <div class="container-page">
        <div class="flex h-[4.5rem] items-center gap-6">

            {{-- Marca --}}
            <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-2.5"
               aria-label="{{ config('queixame.brand.name') }} — página inicial">
                <span class="flex size-9 items-center justify-center rounded-xl text-white transition-transform duration-200 group-hover:scale-105"
                      style="background: linear-gradient(140deg, var(--color-brand-400), var(--color-brand-700)); box-shadow: var(--shadow-brand)"
                      aria-hidden="true">
                    <svg class="size-[1.15rem]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.5 9.4a7 7 0 0 1-7.5 7 7.4 7.4 0 0 1-3.3-.75L2.5 17.5l1.6-4.1A7 7 0 0 1 10 2.5a7 7 0 0 1 7.5 6.9Z"/>
                    </svg>
                </span>
                <span class="text-lg font-extrabold tracking-tight text-ink-900">
                    queixa<span class="text-brand-600">.me</span>
                </span>
            </a>

            {{-- Navegação --}}
            <nav class="hidden flex-1 items-center gap-0.5 lg:flex" aria-label="Navegação principal">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}"
                       class="nav-link {{ request()->is($item['pattern']) ? 'nav-link-active' : '' }}"
                       @if(request()->is($item['pattern'])) aria-current="page" @endif>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Ações --}}
            <div class="ml-auto flex items-center gap-2 lg:ml-0">

                {{-- Pesquisa: abre a sobreposição, não navega --}}
                <button type="button" data-search-open
                        class="group hidden items-center gap-2 rounded-xl border border-ink-200 bg-ink-50/70 py-2 pl-3 pr-2.5 text-sm text-ink-500 transition hover:border-ink-300 hover:bg-surface sm:flex"
                        aria-haspopup="dialog" aria-controls="search-modal">
                    <svg class="size-4 text-ink-400 transition group-hover:text-ink-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3.3 3.3"/>
                    </svg>
                    <span class="hidden font-medium xl:inline">Pesquisar…</span>
                    <kbd class="hidden rounded-md border border-ink-200 bg-surface px-1.5 py-0.5 text-[0.6875rem] font-semibold text-ink-400 xl:block">/</kbd>
                </button>

                <button type="button" data-search-open
                        class="flex size-9 items-center justify-center rounded-xl text-ink-500 transition hover:bg-ink-100 hover:text-ink-800 sm:hidden"
                        aria-label="Pesquisar" aria-haspopup="dialog" aria-controls="search-modal">
                    <svg class="size-[1.15rem]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3.3 3.3"/>
                    </svg>
                </button>

                @auth
                    @php $unread = $user->isConsumer() ? $user->unreadMessagesCount() : 0; @endphp
                    <div class="relative">
                        <button type="button" data-toggle-target="user-menu" aria-expanded="false" aria-haspopup="true"
                                class="flex items-center gap-2 rounded-xl py-1.5 pl-1.5 pr-2.5 text-sm font-semibold text-ink-700 transition hover:bg-ink-100">
                            <span class="relative flex size-8 items-center justify-center rounded-lg text-[0.6875rem] font-extrabold text-white"
                                  style="background: linear-gradient(140deg, var(--color-ink-500), var(--color-ink-800))">
                                {{ $user->initials() }}
                                @if ($unread > 0)
                                    <span class="absolute -right-1 -top-1 flex size-3 items-center justify-center rounded-full bg-accent-500 ring-2 ring-surface"></span>
                                @endif
                            </span>
                            <span class="hidden max-w-24 truncate md:inline">{{ $user->publicDisplayName() }}</span>
                        </button>

                        <div id="user-menu" data-toggle-panel hidden
                             class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl bg-surface py-1.5"
                             style="box-shadow: var(--shadow-float)">
                            @if ($user->isBusiness())
                                <a href="{{ route('business.dashboard') }}" class="block px-4 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">Painel da empresa</a>
                                <a href="{{ route('business.complaints.index') }}" class="block px-4 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">Reclamações recebidas</a>
                                <a href="{{ route('business.stats') }}" class="block px-4 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">Estatísticas</a>
                            @else
                                <a href="{{ route('consumer.dashboard') }}" class="block px-4 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">A minha área</a>
                                <a href="{{ route('consumer.complaints.index') }}" class="block px-4 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">As minhas reclamações</a>
                                <a href="{{ route('consumer.messages.index') }}" class="flex items-center justify-between px-4 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">
                                    Mensagens
                                    @if ($unread > 0)
                                        <span class="rounded-full bg-accent-100 px-2 py-0.5 text-[0.6875rem] font-bold text-accent-700">{{ $unread }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('consumer.activity') }}" class="block px-4 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">Atividade</a>
                            @endif

                            <a href="{{ route('consumer.profile.edit') }}" class="block px-4 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">Perfil e privacidade</a>

                            @if ($user->isModerator())
                                <a href="{{ route('admin.dashboard') }}" class="mt-1 block border-t border-ink-100 px-4 py-2.5 pt-3 text-sm font-bold text-brand-700 hover:bg-brand-50">Administração</a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-ink-100 pt-1">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2.5 text-left text-sm font-medium text-ink-700 hover:bg-ink-50">Terminar sessão</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost hidden sm:inline-flex">Entrar</a>
                @endauth

                <a href="{{ route('complaints.create') }}" class="btn btn-accent btn-sm sm:btn">Reclamar</a>

                <button type="button" data-toggle-target="mobile-nav" aria-expanded="false"
                        class="flex size-9 items-center justify-center rounded-xl text-ink-600 transition hover:bg-ink-100 lg:hidden"
                        aria-label="Abrir menu">
                    <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M3 6h14M3 10h14M3 14h14"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Menu móvel --}}
    <div id="mobile-nav" data-toggle-panel hidden class="border-t border-ink-200 bg-surface lg:hidden">
        <div class="container-page space-y-1 py-3">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" class="block rounded-xl px-3 py-2.5 text-sm font-semibold text-ink-700 hover:bg-ink-50">{{ $item['label'] }}</a>
            @endforeach
            @guest
                <div class="flex gap-2 pt-3">
                    <a href="{{ route('login') }}" class="btn btn-secondary flex-1">Entrar</a>
                    <a href="{{ route('register') }}" class="btn btn-primary flex-1">Criar conta</a>
                </div>
            @endguest
        </div>
    </div>
</header>

@include('partials.search-modal')
