@php
    $columns = [
        'O portal' => [
            ['Sobre nós', route('about')],
            ['Como funciona', route('how-it-works')],
            ['Índices de satisfação', route('methodology')],
            ['Perguntas frequentes', route('faq')],
            ['Contactos', route('contact')],
        ],
        'Consumidores' => [
            ['Fazer uma reclamação', route('complaints.create')],
            ['Ver reclamações', route('complaints.index')],
            ['Ranking de empresas', route('ranking')],
            ['Comparar marcas', route('compare')],
            ['Marcas do mês', route('awards')],
        ],
        'Empresas' => [
            ['Registar empresa', route('register.business')],
            ['Diretório de empresas', route('companies.index')],
            ['Como responder', route('how-it-works').'#empresas'],
            ['Notícias e conteúdos', route('blog.index')],
        ],
        'Legal' => [
            ['Termos e Condições', route('legal.terms')],
            ['Política de Privacidade', route('legal.privacy')],
            ['Proteção de Dados', route('legal.data-protection')],
            ['Política de Moderação', route('legal.moderation')],
        ],
    ];
@endphp

<footer class="mt-16 border-t border-ink-200 bg-white">
    <div class="container-page py-12">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-1">
                <div class="flex items-center gap-2">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-white">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.9 8.9 0 0 1-4-.9L3 21l1.9-4.9A8.4 8.4 0 0 1 12 3.1a8.4 8.4 0 0 1 9 8.4Z"/>
                        </svg>
                    </span>
                    <span class="text-lg font-bold tracking-tight text-ink-900">queixa<span class="text-brand-600">.me</span></span>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-ink-600">
                    {{ config('queixame.brand.tagline') }}
                </p>
                <p class="mt-4 text-xs leading-relaxed text-ink-500">
                    O queixa.me é um portal independente. Não é uma entidade oficial de resolução
                    de litígios e não substitui os canais de reclamação das empresas, o Livro de
                    Reclamações, as entidades reguladoras ou os organismos de resolução
                    alternativa de litígios.
                </p>
            </div>

            @foreach ($columns as $title => $links)
                <nav aria-label="{{ $title }}">
                    <h2 class="text-sm font-semibold text-ink-900">{{ $title }}</h2>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($links as [$label, $url])
                            <li><a href="{{ $url }}" class="text-sm text-ink-600 transition hover:text-brand-700">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach
        </div>

        <div class="mt-10 flex flex-col gap-4 border-t border-ink-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-ink-500">
                &copy; {{ date('Y') }} queixa.me · Todos os direitos reservados
            </p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-ink-500">
                <a href="{{ route('sitemap.index') }}" class="hover:text-ink-800">Mapa do site</a>
                <a href="{{ route('blog.feed') }}" class="hover:text-ink-800">RSS</a>
                <a href="mailto:{{ config('queixame.brand.dpo_email') }}" class="hover:text-ink-800">Encarregado de Proteção de Dados</a>
            </div>
        </div>
    </div>
</footer>
