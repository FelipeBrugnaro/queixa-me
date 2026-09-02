@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')

{{-- ========================= HERO =========================
     Sem gradientes decorativos: a prova de que o portal funciona são os
     casos reais e os números, por isso são eles que ocupam o espaço nobre.
--}}
<section class="border-b border-ink-200 bg-surface">
    <div class="container-page py-14 sm:py-20">
        <div class="grid items-start gap-12 lg:grid-cols-12 lg:gap-16">

            <div class="lg:col-span-7">
                <p class="eyebrow">Portal independente de reclamações</p>

                <h1 class="mt-5 text-[2.6rem] leading-[1.05] sm:text-6xl">
                    Tiveste um problema?<br>
                    <span class="text-brand-700">Conta o que aconteceu.</span>
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-ink-600">
                    Publica a tua reclamação, dá à empresa a oportunidade de responder e acompanha
                    a resolução do princípio ao fim. Antes de comprares, vê como cada marca trata
                    quem já reclamou.
                </p>

                <form action="{{ route('search') }}" method="GET" role="search" class="mt-8 max-w-lg">
                    <label for="hero-search" class="sr-only">Pesquisar empresa</label>
                    <div class="flex items-center gap-2 rounded-lg border border-ink-300 bg-surface p-1.5 transition focus-within:border-brand-600 focus-within:ring-2 focus-within:ring-brand-600/20">
                        <svg class="ml-2.5 size-4 shrink-0 text-ink-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3.3 3.3"/>
                        </svg>
                        <input id="hero-search" type="search" name="q" placeholder="Procura uma empresa, marca ou loja…"
                               class="min-w-0 flex-1 border-0 bg-transparent py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none">
                        <button type="submit" class="btn btn-primary shrink-0">Pesquisar</button>
                    </div>
                </form>

                <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3">
                    <a href="{{ route('complaints.create') }}" class="btn btn-primary btn-lg">
                        Fazer uma reclamação
                    </a>
                    <a href="{{ route('ranking') }}" class="text-sm font-semibold text-ink-700 underline decoration-ink-300 underline-offset-4 transition hover:decoration-ink-600">
                        Ver ranking de empresas
                    </a>
                </div>
            </div>

            {{-- Painel de prova: o caso mais recente que acabou bem --}}
            <div class="lg:col-span-5">
                @php $showcase = $answeredComplaints->first(); @endphp

                @if ($showcase)
                    <div class="card overflow-hidden">
                        <div class="flex items-center justify-between gap-3 border-b border-ink-100 bg-brand-50/60 px-5 py-3">
                            <p class="eyebrow text-brand-800">Resolvido esta semana</p>
                            <span class="flex size-5 items-center justify-center rounded-full bg-brand-600 text-white" aria-hidden="true">
                                <svg class="size-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.7 6.3a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4 0l-3-3a1 1 0 1 1 1.4-1.4L9 12.6l6.3-6.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                        </div>

                        <div class="p-5">
                            <div class="flex items-center gap-3">
                                <x-company-avatar :company="$showcase->company" size="sm" />
                                <p class="truncate text-sm font-semibold text-ink-800">{{ $showcase->company?->name }}</p>
                            </div>

                            <h2 class="mt-3 text-lg leading-snug">
                                <a href="{{ $showcase->url() }}" class="transition hover:text-brand-800">{{ $showcase->title }}</a>
                            </h2>

                            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-ink-600">
                                {{ $showcase->excerpt(180) }}
                            </p>

                            <div class="mt-4 flex items-center justify-between gap-3 border-t border-ink-100 pt-4">
                                @if ($showcase->rating)
                                    <x-stars :rating="$showcase->rating" show-value />
                                @endif
                                <span class="text-xs text-ink-400">
                                    resolvido {{ $showcase->resolved_at?->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Números do portal, em fio --}}
                @if ($stats['complaints'] > 0)
                    <dl class="mt-6 grid grid-cols-2 gap-x-6 gap-y-5 border-t border-ink-200 pt-6">
                        @foreach ([
                            ['Reclamações publicadas', number_format($stats['complaints'], 0, ',', ' ')],
                            ['Empresas no portal', number_format($stats['companies'], 0, ',', ' ')],
                            ['Taxa de resposta', $stats['response_rate'] !== null ? $stats['response_rate'].'%' : '—'],
                            ['Taxa de resolução', $stats['resolution_rate'] !== null ? $stats['resolution_rate'].'%' : '—'],
                        ] as [$label, $value])
                            <div>
                                <dd class="font-display text-3xl leading-none text-ink-900">{{ $value }}</dd>
                                <dt class="mt-1.5 text-xs text-ink-500">{{ $label }}</dt>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ========================= COMO FUNCIONA ========================= --}}
<section class="border-b border-ink-200 py-20" aria-labelledby="como-funciona">
    <div class="container-page">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-4">
                <p class="eyebrow">O processo</p>
                <h2 id="como-funciona" class="mt-4 text-3xl sm:text-4xl">Como funciona</h2>
                <p class="mt-5 text-[0.9375rem] leading-relaxed text-ink-600">
                    Quatro passos. Sem burocracia, sem custos e sem intermediários.
                </p>
                <a href="{{ route('how-it-works') }}" class="mt-6 inline-flex text-sm font-semibold text-brand-700 transition hover:text-brand-900">
                    Ver o processo em detalhe <span aria-hidden="true" class="ml-1">&rarr;</span>
                </a>
            </div>

            {{-- Lista numerada com fios: lê-se como um procedimento, que é
                 exatamente o que é. --}}
            <ol class="lg:col-span-8">
                @foreach ([
                    ['Apresenta a tua reclamação', 'Descreves o que aconteceu, quando, e o que esperas que a empresa faça. Podes juntar faturas, fotografias e comprovativos.'],
                    ['A equipa analisa', 'Antes de publicar, verificamos que a reclamação não expõe dados pessoais nem contém conteúdo abusivo. Se faltar algo, dizemos-te o quê.'],
                    ['A empresa responde', 'A empresa é notificada e pode responder publicamente ou tratar contigo em privado o que não deve ser público.'],
                    ['Confirmas a resolução', 'Só tu podes dar o problema como resolvido. É essa confirmação que conta para os índices da empresa.'],
                ] as $index => [$title, $description])
                    <li class="flex gap-6 border-t border-ink-200 py-6 first:border-t-0 first:pt-0">
                        <span class="font-display w-8 shrink-0 text-3xl leading-none text-brand-600">
                            {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-lg" style="font-family: var(--font-sans); font-weight: 600; letter-spacing: -0.015em">{{ $title }}</h3>
                            <p class="mt-1.5 text-[0.9375rem] leading-relaxed text-ink-600">{{ $description }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>

{{-- ========================= RANKING ========================= --}}
@if ($topCompanies->isNotEmpty())
    <section class="border-b border-ink-200 py-20" aria-labelledby="ranking">
        <div class="container-page">
            <x-section-header
                eyebrow="Índice de satisfação"
                title="Quem trata melhor quem reclama"
                description="Calculado sobre os últimos 12 meses a partir da taxa de resposta, da resolução confirmada pelo consumidor, da avaliação e do tempo de resposta. Nunca a partir do número de reclamações."
                :href="route('ranking')"
                link-label="Ranking completo" />

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($topCompanies as $index => $company)
                    <x-company-card :company="$company" :rank="$index + 1" />
                @endforeach
            </div>

            <p class="mt-6 text-xs text-ink-500">
                <a href="{{ route('methodology') }}" class="underline decoration-ink-300 underline-offset-2 hover:text-ink-800">
                    Como calculamos o índice de satisfação
                </a>
            </p>
        </div>
    </section>
@endif

{{-- ========================= MARCAS DO MÊS ========================= --}}
@if ($awards->isNotEmpty())
    <section class="border-b border-ink-200 bg-surface py-20" aria-labelledby="marcas-do-mes">
        <div class="container-page">
            <x-section-header
                eyebrow="Distinções do mês"
                title="Marcas do mês"
                description="Apuradas automaticamente a partir dos indicadores públicos. Não são compradas nem patrocinadas."
                :href="route('awards')" />

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($awards as $award)
                    <article class="card card-hover group relative flex flex-col p-5">
                        <p class="eyebrow text-brand-700">{{ $award->award_type->label() }}</p>

                        <div class="mt-4 flex items-center gap-3">
                            <x-company-avatar :company="$award->company" size="sm" />
                            <h3 class="min-w-0 flex-1 truncate text-base" style="font-family: var(--font-sans); font-weight: 600">
                                <a href="{{ $award->company->url() }}" class="transition hover:text-brand-800">
                                    <span class="absolute inset-0" aria-hidden="true"></span>
                                    {{ $award->company->name }}
                                </a>
                            </h3>
                        </div>

                        @if ($award->metric_value !== null)
                            <p class="font-display mt-4 text-3xl leading-none text-ink-900">
                                {{ number_format($award->metric_value, 0, ',', '') }}{{ $award->award_type->value === 'best_service' ? 'h' : ($award->award_type->value === 'brand_of_the_month' ? '' : '%') }}
                            </p>
                        @endif

                        <p class="mt-3 text-xs leading-relaxed text-ink-500">
                            {{ $award->editorial_note ?: $award->award_type->description() }}
                        </p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ========================= RECLAMAÇÕES RECENTES ========================= --}}
@if ($recentComplaints->isNotEmpty())
    <section class="border-b border-ink-200 py-20" aria-labelledby="recentes">
        <div class="container-page">
            <x-section-header
                eyebrow="Publicadas após análise"
                title="Reclamações recentes"
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

{{-- ========================= SETORES ========================= --}}
@if ($categories->isNotEmpty())
    <section class="border-b border-ink-200 bg-surface py-20" aria-labelledby="categorias">
        <div class="container-page">
            <x-section-header
                eyebrow="Diretório"
                title="Explorar por setor"
                :href="route('companies.index')"
                link-label="Ver diretório" />

            {{-- Lista de fios em vez de grelha de cartões: é um índice, e um
                 índice lê-se melhor como lista. --}}
            <ul class="grid gap-x-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($categories as $category)
                    <li class="border-b border-ink-100">
                        <a href="{{ route('companies.category', $category) }}"
                           class="group flex items-baseline justify-between gap-3 py-3.5 transition">
                            <span class="text-sm font-medium text-ink-800 transition group-hover:text-brand-700">{{ $category->name }}</span>
                            <span class="text-xs tabular-nums text-ink-400">{{ $category->companies_count }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

{{-- ========================= NOTÍCIAS ========================= --}}
@if ($posts->isNotEmpty())
    <section class="border-b border-ink-200 py-20" aria-labelledby="noticias">
        <div class="container-page">
            <x-section-header
                eyebrow="Direitos do consumidor"
                title="Notícias e conteúdos"
                :href="route('blog.index')" />

            <div class="grid gap-x-8 gap-y-10 md:grid-cols-3">
                @foreach ($posts as $post)
                    <article class="group relative">
                        @if ($post->coverUrl())
                            <img src="{{ $post->coverUrl() }}" alt="{{ $post->cover_alt ?? '' }}" loading="lazy" decoding="async"
                                 class="mb-4 aspect-3/2 w-full rounded-lg border border-ink-200 object-cover" width="640" height="427">
                        @endif

                        @if ($post->category)
                            <p class="eyebrow text-brand-700">{{ $post->category->name }}</p>
                        @endif

                        <h3 class="mt-2 text-xl leading-snug">
                            <a href="{{ $post->url() }}" class="transition hover:text-brand-800">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                {{ $post->title }}
                            </a>
                        </h3>

                        <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-600">{{ $post->excerpt }}</p>

                        <p class="mt-3 text-xs text-ink-400">
                            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->translatedFormat('j \d\e F Y') }}</time>
                            <span class="text-ink-300" aria-hidden="true">/</span> {{ $post->reading_minutes }} min
                        </p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ========================= CTA EMPRESAS ========================= --}}
<section class="bg-ink-950 py-20 text-ink-300" aria-labelledby="cta-empresas">
    <div class="container-page">
        <div class="grid items-center gap-10 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-7">
                <p class="eyebrow text-brand-300">Para empresas</p>
                <h2 id="cta-empresas" class="mt-4 text-3xl text-white sm:text-4xl">
                    Responder também é uma oportunidade.
                </h2>
                <p class="mt-5 max-w-xl text-[0.9375rem] leading-relaxed text-ink-400">
                    Reivindica a ficha da tua marca, recebe as reclamações no momento em que são
                    publicadas e mostra publicamente como resolves. No queixa.me, quem responde e
                    resolve sobe no ranking — quem ignora, desce.
                </p>
            </div>

            <div class="flex flex-wrap gap-3 lg:col-span-5 lg:justify-end">
                <a href="{{ route('register.business') }}" class="btn btn-lg bg-white text-ink-900 hover:bg-ink-100">
                    Criar conta de empresa
                </a>
                <a href="{{ route('methodology') }}" class="btn btn-lg border border-ink-700 text-ink-200 hover:border-ink-500 hover:text-white">
                    Ver metodologia
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
