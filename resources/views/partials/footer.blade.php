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

{{-- Sem margem superior: cada página traz o seu próprio espaçamento
     inferior, e assim a secção final escura da homepage flui directamente
     para o rodapé em vez de deixar uma faixa clara entre dois blocos. --}}
<footer class="bg-ink-950 text-ink-300">
    <div class="container-page py-16">
        <div class="grid gap-12 lg:grid-cols-[1.4fr_2.6fr]">

            {{-- Identidade e ressalva --}}
            <div class="max-w-sm">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-8 items-center justify-center rounded-md bg-brand-600 text-white" aria-hidden="true">
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.5 9.4a7 7 0 0 1-7.5 7 7.4 7.4 0 0 1-3.3-.75L2.5 17.5l1.6-4.1A7 7 0 0 1 10 2.5a7 7 0 0 1 7.5 6.9Z"/>
                        </svg>
                    </span>
                    <span class="font-display text-[1.35rem] leading-none text-white">
                        queixa<span class="text-brand-300">.me</span>
                    </span>
                </div>

                <p class="font-display mt-6 text-xl leading-snug text-ink-100">
                    {{ config('queixame.brand.tagline') }}
                </p>

                {{-- A ressalva legal é destacada, não escondida: é uma questão
                     de honestidade com quem chega ao portal à espera de uma
                     entidade oficial. --}}
                <p class="mt-6 border-l-2 border-ink-700 pl-4 text-xs leading-relaxed text-ink-400">
                    O queixa.me é um portal independente. Não é uma entidade oficial de resolução
                    de litígios e não substitui os canais de reclamação das empresas, o Livro de
                    Reclamações, as entidades reguladoras ou os organismos de resolução
                    alternativa de litígios.
                </p>
            </div>

            {{-- Navegação --}}
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($columns as $title => $links)
                    <nav aria-label="{{ $title }}">
                        <h2 class="text-[0.6875rem] font-semibold uppercase tracking-[0.08em] text-ink-500"
                            style="font-family: var(--font-sans)">
                            {{ $title }}
                        </h2>
                        <ul class="mt-4 space-y-2.5">
                            @foreach ($links as [$label, $url])
                                <li>
                                    <a href="{{ $url }}" class="text-sm text-ink-300 transition hover:text-white">{{ $label }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endforeach
            </div>
        </div>

        <div class="mt-14 flex flex-col gap-4 border-t border-ink-800 pt-8 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-ink-500">&copy; {{ date('Y') }} queixa.me · Todos os direitos reservados</p>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-ink-500">
                <a href="{{ route('sitemap.index') }}" class="transition hover:text-ink-200">Mapa do site</a>
                <a href="{{ route('blog.feed') }}" class="transition hover:text-ink-200">RSS</a>
                <a href="mailto:{{ config('queixame.brand.dpo_email') }}" class="transition hover:text-ink-200">Encarregado de Proteção de Dados</a>
            </div>
        </div>
    </div>
</footer>
