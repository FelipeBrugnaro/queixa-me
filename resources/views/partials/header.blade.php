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

<header class="sticky top-0 z-40 border-b border-ink-200/70 bg-white/90 backdrop-blur">
    <div class="container-page">
        <div class="flex h-16 items-center justify-between gap-4">
            {{-- Marca --}}
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="{{ config('queixame.brand.name') }} — página inicial">
                <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.9 8.9 0 0 1-4-.9L3 21l1.9-4.9A8.4 8.4 0 0 1 12 3.1a8.4 8.4 0 0 1 9 8.4Z"/>
                    </svg>
                </span>
                <span class="text-lg font-bold tracking-tight text-ink-900">queixa<span class="text-brand-600">.me</span></span>
            </a>

            {{-- Navegação principal --}}
            <nav class="hidden items-center gap-0.5 lg:flex" aria-label="Navegação principal">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}"
                       class="nav-link {{ request()->is($item['pattern']) ? 'nav-link-active' : '' }}"
                       @if(request()->is($item['pattern'])) aria-current="page" @endif>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Ações --}}
            <div class="flex items-center gap-2">
                <a href="{{ route('search') }}" class="btn btn-ghost hidden sm:inline-flex" aria-label="Pesquisar">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.4 9.83l3.63 3.64a.75.75 0 1 0 1.06-1.06l-3.63-3.64A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd"/>
                    </svg>
                    <span class="hidden xl:inline">Pesquisar</span>
                </a>

                @auth
                    @php $unread = $user->isConsumer() ? $user->unreadMessagesCount() : 0; @endphp
                    <div class="relative">
                        <button type="button" data-toggle-target="user-menu" aria-expanded="false" aria-haspopup="true"
                                class="flex items-center gap-2 rounded-xl px-2 py-1.5 text-sm font-medium text-ink-700 hover:bg-ink-100">
                            <span class="relative flex size-8 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">
                                {{ $user->initials() }}
                                @if ($unread > 0)
                                    <span class="absolute -right-0.5 -top-0.5 size-2.5 rounded-full bg-rose-500 ring-2 ring-white"></span>
                                @endif
                            </span>
                            <span class="hidden max-w-28 truncate md:inline">{{ $user->publicDisplayName() }}</span>
                        </button>

                        <div id="user-menu" data-toggle-panel hidden
                             class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl bg-white py-1.5 ring-1 ring-ink-200 shadow-lg">
                            @if ($user->isBusiness())
                                <a href="{{ route('business.dashboard') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">Painel da empresa</a>
                                <a href="{{ route('business.complaints.index') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">Reclamações recebidas</a>
                                <a href="{{ route('business.stats') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">Estatísticas</a>
                            @else
                                <a href="{{ route('consumer.dashboard') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">A minha área</a>
                                <a href="{{ route('consumer.complaints.index') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">As minhas reclamações</a>
                                <a href="{{ route('consumer.messages.index') }}" class="flex items-center justify-between px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">
                                    Mensagens
                                    @if ($unread > 0)
                                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">{{ $unread }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('consumer.activity') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">Atividade</a>
                            @endif

                            <a href="{{ route('consumer.profile.edit') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">Perfil e privacidade</a>

                            @if ($user->isModerator())
                                <a href="{{ route('admin.dashboard') }}" class="block border-t border-ink-100 px-4 py-2 text-sm font-medium text-brand-700 hover:bg-brand-50">Administração</a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}" class="border-t border-ink-100">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-ink-700 hover:bg-ink-50">Terminar sessão</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost hidden sm:inline-flex">Entrar</a>
                @endauth

                <a href="{{ route('complaints.create') }}" class="btn btn-primary btn-sm sm:btn">Reclamar</a>

                <button type="button" data-toggle-target="mobile-nav" aria-expanded="false"
                        class="btn btn-ghost px-2 lg:hidden" aria-label="Abrir menu">
                    <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M2 5.75A.75.75 0 0 1 2.75 5h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 5.75Zm0 4.5A.75.75 0 0 1 2.75 9.5h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Zm0 4.5a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Menu móvel --}}
    <div id="mobile-nav" data-toggle-panel hidden class="border-t border-ink-200 bg-white lg:hidden">
        <div class="container-page space-y-1 py-3">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ route('search') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">Pesquisar</a>
            @guest
                <div class="flex gap-2 pt-2">
                    <a href="{{ route('login') }}" class="btn btn-secondary flex-1">Entrar</a>
                    <a href="{{ route('register') }}" class="btn btn-primary flex-1">Criar conta</a>
                </div>
            @endguest
        </div>
    </div>
</header>
