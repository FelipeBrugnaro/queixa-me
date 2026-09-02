@extends('layouts.app')

@section('content')
<div class="container-page py-8">

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl font-bold sm:text-4xl">
            @if (isset($activeCategory))
                {{ $activeCategory->name }}
            @elseif (isset($activeTag))
                Artigos sobre {{ $activeTag->name }}
            @else
                Notícias e direitos do consumidor
            @endif
        </h1>
        <p class="mt-3 text-ink-600">
            {{ $activeCategory->description ?? 'O que precisas de saber antes, durante e depois de comprar.' }}
        </p>
    </header>

    <nav aria-label="Categorias" class="mb-8 flex flex-wrap gap-2">
        <a href="{{ route('blog.index') }}"
           class="badge {{ ! isset($activeCategory) ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-ink-700 ring-ink-200 hover:bg-ink-50' }}">
            Tudo
        </a>
        @foreach ($categories as $category)
            <a href="{{ route('blog.category', $category) }}"
               class="badge {{ ($activeCategory ?? null)?->id === $category->id ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-ink-700 ring-ink-200 hover:bg-ink-50' }}">
                {{ $category->name }}
            </a>
        @endforeach
    </nav>

    @if ($featured)
        <article class="card card-hover mb-8 overflow-hidden lg:flex">
            @if ($featured->coverUrl())
                <img src="{{ $featured->coverUrl() }}" alt="{{ $featured->cover_alt ?? '' }}"
                     class="aspect-16/9 w-full object-cover lg:aspect-auto lg:w-2/5" width="800" height="450">
            @endif
            <div class="card-body flex-1">
                <span class="badge bg-accent-500/10 text-accent-600 ring-accent-500/20">Em destaque</span>
                <h2 class="mt-3 text-xl font-bold sm:text-2xl">
                    <a href="{{ $featured->url() }}" class="hover:text-brand-700">{{ $featured->title }}</a>
                </h2>
                <p class="mt-2 text-ink-600">{{ $featured->excerpt }}</p>
                <p class="mt-4 text-xs text-ink-400">
                    <time datetime="{{ $featured->published_at?->toDateString() }}">{{ $featured->published_at?->translatedFormat('j \d\e F \d\e Y') }}</time>
                    <span aria-hidden="true">·</span> {{ $featured->reading_minutes }} min de leitura
                </p>
            </div>
        </article>
    @endif

    @if ($posts->isEmpty())
        <x-empty-state title="Ainda não há artigos publicados" />
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <article class="card card-hover flex flex-col overflow-hidden">
                    @if ($post->coverUrl())
                        <img src="{{ $post->coverUrl() }}" alt="{{ $post->cover_alt ?? '' }}" loading="lazy" decoding="async"
                             class="aspect-16/9 w-full object-cover" width="640" height="360">
                    @endif
                    <div class="card-body flex flex-1 flex-col">
                        @if ($post->category)
                            <a href="{{ route('blog.category', $post->category) }}" class="text-xs font-semibold uppercase tracking-wide text-brand-700">
                                {{ $post->category->name }}
                            </a>
                        @endif
                        <h2 class="mt-2 font-semibold leading-snug">
                            <a href="{{ $post->url() }}" class="hover:text-brand-700">{{ $post->title }}</a>
                        </h2>
                        <p class="mt-2 line-clamp-3 flex-1 text-sm text-ink-600">{{ $post->excerpt }}</p>
                        <p class="mt-4 text-xs text-ink-400">
                            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->translatedFormat('j M Y') }}</time>
                            <span aria-hidden="true">·</span> {{ $post->reading_minutes }} min
                        </p>
                    </div>
                </article>
            @endforeach
        </div>

        {{ $posts->links() }}
    @endif
</div>
@endsection
