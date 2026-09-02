@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')

{{-- ================= HERO ================= --}}
<section class="relative overflow-hidden bg-white">
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 -top-40 -z-0 blur-3xl">
        <div class="mx-auto aspect-1155/678 w-[72rem] bg-linear-to-tr from-brand-200 to-brand-50 opacity-40"
             style="clip-path: polygon(74% 44%, 100% 62%, 97% 26%, 85% 0%, 80% 2%, 72% 32%, 60% 62%, 52% 68%, 47% 58%, 45% 35%, 27% 76%, 0% 64%, 18% 100%, 27% 76%, 76% 97%, 74% 44%)"></div>
    </div>

    <div class="container-page relative py-16 sm:py-24">
        <div class="mx-auto max-w-3xl text-center">
            <span class="badge bg-brand-50 text-brand-700 ring-brand-200">
                <span class="size-1.5 rounded-full bg-brand-500"></span>
                Portal independente de reclamações
            </span>

            <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-6xl">
                Tiveste um problema?<br class="hidden sm:block">
                <span class="text-brand-700">Conta-nos o que aconteceu.</span>
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-ink-600">
                Publica a tua reclamação, dá à empresa a oportunidade de responder e acompanha
                a resolução do princípio ao fim. Antes de comprares, vê como cada marca trata
                quem já reclamou.
            </p>

            {{-- Pesquisa rápida de empresa --}}
            <form action="{{ route('search') }}" method="GET" role="search" class="mx-auto mt-8 max-w-xl">
                <label for="hero-search" class="sr-only">Pesquisar empresa</label>
                <div class="flex items-center gap-2 rounded-2xl bg-white p-2 ring-1 ring-ink-200 shadow-sm focus-within:ring-2 focus-within:ring-brand-600">
                    <svg class="ml-2 size-5 shrink-0 text-ink-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.4 9.83l3.63 3.64a.75.75 0 1 0 1.06-1.06l-3.63-3.64A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd"/>
                    </svg>
                    <input id="hero-search" type="search" name="q" placeholder="Procura uma empresa, marca ou loja…"
                           class="min-w-0 flex-1 border-0 bg-transparent py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:ring-0">
                    <button type="submit" class="btn btn-primary shrink-0">Pesquisar</button>
                </div>
            </form>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('complaints.create') }}" class="btn btn-primary btn-lg">
                    Fazer uma reclamação
                </a>
                <a href="{{ route('ranking') }}" class="btn btn-secondary btn-lg">
                    Ver ranking de empresas
                </a>
            </div>
        </div>

        {{-- Números do portal --}}
        @if ($stats['complaints'] > 0)
            <dl class="mx-auto mt-14 grid max-w-4xl grid-cols-2 gap-px overflow-hidden rounded-2xl bg-ink-200 ring-1 ring-ink-200 sm:grid-cols-4">
                @foreach ([
                    ['Reclamações publicadas', number_format($stats['complaints'], 0, ',', ' ')],
                    ['Empresas no portal', number_format($stats['companies'], 0, ',', ' ')],
                    ['Taxa de resposta', $stats['response_rate'] !== null ? $stats['response_rate'].'%' : '—'],
                    ['Taxa de resolução', $stats['resolution_rate'] !== null ? $stats['resolution_rate'].'%' : '—'],
                ] as [$label, $value])
                    <div class="bg-white px-4 py-5 text-center">
                        <dt class="text-xs font-medium uppercase tracking-wide text-ink-500">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-bold text-ink-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</section>

{{-- ================= COMO FUNCIONA ================= --}}
<section class="border-y border-ink-200 bg-ink-50/60 py-16" aria-labelledby="como-funciona">
    <div class="container-page">
        <div class="mx-auto max-w-2xl text-center">
            <h2 id="como-funciona" class="text-2xl font-semibold sm:text-3xl">Como funciona</h2>
            <p class="mt-3 text-ink-600">
                Quatro passos simples. Sem burocracia, sem custos e sem intermediários.
            </p>
        </div>

        <ol class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['1', 'Apresenta a tua reclamação', 'Descreves o que aconteceu, quando, e o que esperas que a empresa faça. Podes juntar faturas, fotografias e comprovativos.'],
                ['2', 'A equipa analisa', 'Antes de publicar, verificamos que a reclamação não expõe dados pessoais nem contém conteúdo abusivo. Se faltar algo, dizemos-te o quê.'],
                ['3', 'A empresa responde', 'A empresa é notificada e pode responder publicamente ou tratar contigo em privado o que não deve ser público.'],
                ['4', 'Confirmas a resolução', 'Só tu podes dar o problema como resolvido. É essa confirmação que conta para os índices da empresa.'],
            ] as [$number, $title, $description])
                <li class="card">
                    <div class="card-body">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-sm font-bold text-white">{{ $number }}</span>
                        <h3 class="mt-4 text-base font-semibold">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $description }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <p class="mt-8 text-center text-sm text-ink-500">
            <a href="{{ route('how-it-works') }}" class="font-semibold text-brand-700 hover:text-brand-800">
                Ver o processo em detalhe <span aria-hidden="true">&rarr;</span>
            </a>
        </p>
    </div>
</section>

{{-- ================= MARCAS DO MÊS ================= --}}
@if ($awards->isNotEmpty())
    <section class="py-16" aria-labelledby="marcas-do-mes">
        <div class="container-page">
            <x-section-header
                title="Marcas do mês"
                description="As empresas que melhor responderam e resolveram no último mês. Distinções calculadas a partir dos indicadores públicos, não de opiniões."
                :href="route('awards')" />

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($awards as $award)
                    <article class="card card-hover overflow-hidden">
                        <div class="bg-linear-to-br from-brand-600 to-brand-800 px-5 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand-100">{{ $award->award_type->label() }}</p>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center gap-3">
                                <x-company-avatar :company="$award->company" size="md" />
                                <div class="min-w-0">
                                    <h3 class="truncate font-semibold">
                                        <a href="{{ $award->company->url() }}" class="hover:text-brand-700">{{ $award->company->name }}</a>
                                    </h3>
                                    @if ($award->metric_value !== null)
                                        <p class="text-xs text-ink-500">{{ number_format($award->metric_value, 0, ',', '') }}{{ $award->award_type->value === 'best_service' ? ' h' : '%' }}</p>
                                    @endif
                                </div>
                            </div>
                            <p class="mt-3 text-xs leading-relaxed text-ink-500">{{ $award->editorial_note ?: $award->award_type->description() }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ================= RECLAMAÇÕES RECENTES ================= --}}
@if ($recentComplaints->isNotEmpty())
    <section class="border-t border-ink-200 bg-ink-50/60 py-16" aria-labelledby="recentes">
        <div class="container-page">
            <x-section-header
                title="Reclamações recentes"
                description="Publicadas depois de análise da nossa equipa."
                :href="route('complaints.index')"
                link-label="Ver todas" />

            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($recentComplaints as $complaint)
                    <x-complaint-card :complaint="$complaint" />
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ================= RESOLVIDAS ================= --}}
@if ($answeredComplaints->isNotEmpty())
    <section class="py-16" aria-labelledby="resolvidas">
        <div class="container-page">
            <x-section-header
                title="Problemas resolvidos"
                description="Casos em que o consumidor confirmou que a empresa resolveu. É este desfecho que o portal existe para promover."
                :href="route('complaints.index', ['estado' => 'resolved'])"
                link-label="Ver resolvidas" />

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($answeredComplaints as $complaint)
                    <x-complaint-card :complaint="$complaint" compact />
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ================= RANKING ================= --}}
@if ($topCompanies->isNotEmpty())
    <section class="border-t border-ink-200 bg-white py-16" aria-labelledby="ranking">
        <div class="container-page">
            <x-section-header
                title="Quem trata melhor quem reclama"
                description="Índice de satisfação dos últimos 12 meses, calculado a partir da taxa de resposta, da resolução confirmada, da avaliação dos consumidores e do tempo de resposta."
                :href="route('ranking')"
                link-label="Ver ranking completo" />

            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($topCompanies as $index => $company)
                    <x-company-card :company="$company" :rank="$index + 1" />
                @endforeach
            </div>

            <p class="mt-6 text-center text-xs text-ink-500">
                <a href="{{ route('methodology') }}" class="font-medium underline underline-offset-2 hover:text-ink-700">
                    Como calculamos o índice de satisfação
                </a>
            </p>
        </div>
    </section>
@endif

{{-- ================= CATEGORIAS ================= --}}
@if ($categories->isNotEmpty())
    <section class="border-t border-ink-200 py-16" aria-labelledby="categorias">
        <div class="container-page">
            <x-section-header
                title="Explorar por setor"
                description="Encontra empresas e reclamações no setor que te interessa."
                :href="route('companies.index')"
                link-label="Ver diretório" />

            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($categories as $category)
                    <li>
                        <a href="{{ route('companies.category', $category) }}"
                           class="card card-hover flex items-center justify-between px-4 py-3.5">
                            <span class="truncate text-sm font-medium text-ink-800">{{ $category->name }}</span>
                            <span class="ml-2 shrink-0 text-xs text-ink-400">{{ $category->companies_count }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

{{-- ================= BLOG ================= --}}
@if ($posts->isNotEmpty())
    <section class="border-t border-ink-200 bg-ink-50/60 py-16" aria-labelledby="noticias">
        <div class="container-page">
            <x-section-header
                title="Notícias e direitos do consumidor"
                description="O que precisas de saber antes, durante e depois de comprar."
                :href="route('blog.index')" />

            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($posts as $post)
                    <article class="card card-hover overflow-hidden">
                        @if ($post->coverUrl())
                            <img src="{{ $post->coverUrl() }}" alt="{{ $post->cover_alt ?? '' }}" loading="lazy" decoding="async"
                                 class="aspect-16/9 w-full object-cover" width="640" height="360">
                        @endif
                        <div class="card-body">
                            @if ($post->category)
                                <a href="{{ route('blog.category', $post->category) }}" class="text-xs font-semibold uppercase tracking-wide text-brand-700">
                                    {{ $post->category->name }}
                                </a>
                            @endif
                            <h3 class="mt-2 font-semibold leading-snug">
                                <a href="{{ $post->url() }}" class="hover:text-brand-700">{{ $post->title }}</a>
                            </h3>
                            <p class="mt-2 line-clamp-2 text-sm text-ink-600">{{ $post->excerpt }}</p>
                            <p class="mt-3 text-xs text-ink-400">
                                <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->translatedFormat('j \d\e F Y') }}</time>
                                <span aria-hidden="true">·</span> {{ $post->reading_minutes }} min de leitura
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ================= CTA EMPRESAS ================= --}}
<section class="py-16" aria-labelledby="cta-empresas">
    <div class="container-page">
        <div class="overflow-hidden rounded-3xl bg-ink-900 px-6 py-14 text-center sm:px-16">
            <h2 id="cta-empresas" class="text-2xl font-semibold text-white sm:text-3xl">
                É uma empresa? Responder também é uma oportunidade.
            </h2>
            <p class="mx-auto mt-4 max-w-2xl text-ink-300">
                Reivindica a ficha da tua marca, recebe as reclamações no momento em que são
                publicadas e mostra publicamente como resolves. No queixa.me, quem responde
                e resolve sobe no ranking — quem ignora, desce.
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('register.business') }}" class="btn btn-primary btn-lg">Criar conta de empresa</a>
                <a href="{{ route('methodology') }}" class="btn btn-lg bg-white/10 text-white hover:bg-white/20">Como é calculado o índice</a>
            </div>
        </div>
    </div>
</section>

@endsection
