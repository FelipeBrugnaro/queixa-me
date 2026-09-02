@extends('layouts.app')

@section('content')
<div class="container-page py-8">
    <div class="lg:grid lg:grid-cols-[1fr_18rem] lg:gap-10">

        <article class="min-w-0">
            <header>
                @if ($post->category)
                    <a href="{{ route('blog.category', $post->category) }}" class="text-xs font-semibold uppercase tracking-wide text-brand-700">
                        {{ $post->category->name }}
                    </a>
                @endif
                <h1 class="mt-2 text-3xl font-bold leading-tight sm:text-4xl">{{ $post->title }}</h1>
                <p class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-500">
                    <span>{{ $post->author?->publicDisplayName() ?? 'Equipa queixa.me' }}</span>
                    <span aria-hidden="true">·</span>
                    <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->translatedFormat('j \d\e F \d\e Y') }}</time>
                    <span aria-hidden="true">·</span>
                    <span>{{ $post->reading_minutes }} min de leitura</span>
                </p>
            </header>

            @if ($post->coverUrl())
                <img src="{{ $post->coverUrl() }}" alt="{{ $post->cover_alt ?? '' }}"
                     class="mt-6 aspect-16/9 w-full rounded-2xl object-cover" width="1200" height="675">
            @endif

            @if ($post->excerpt)
                <p class="mt-6 border-l-4 border-brand-200 pl-4 text-lg leading-relaxed text-ink-700">{{ $post->excerpt }}</p>
            @endif

            <div class="prose-qm mt-8">{!! $post->body !!}</div>

            @if ($post->tags->isNotEmpty())
                <ul class="mt-8 flex flex-wrap gap-2">
                    @foreach ($post->tags as $tag)
                        <li>
                            <a href="{{ route('blog.tag', $tag) }}" class="badge bg-ink-100 text-ink-700 ring-ink-200 hover:bg-ink-200">
                                #{{ $tag->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-10 rounded-2xl bg-ink-900 px-6 py-8 text-center">
                <h2 class="text-xl font-semibold text-white">Tiveste um problema semelhante?</h2>
                <p class="mx-auto mt-2 max-w-lg text-sm text-ink-300">
                    Publica a tua reclamação e dá à empresa a oportunidade de resolver.
                </p>
                <a href="{{ route('complaints.create') }}" class="btn btn-primary mt-5">Fazer uma reclamação</a>
            </div>
        </article>

        <aside class="mt-10 lg:mt-0">
            <div class="lg:sticky lg:top-24">
                @if ($related->isNotEmpty())
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-500">Artigos relacionados</h2>
                    <ul class="space-y-3">
                        @foreach ($related as $item)
                            <li class="card">
                                <a href="{{ $item->url() }}" class="block p-4 hover:bg-ink-50">
                                    <p class="line-clamp-2 text-sm font-medium text-ink-800">{{ $item->title }}</p>
                                    <p class="mt-1.5 text-xs text-ink-400">{{ $item->published_at?->translatedFormat('j M Y') }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </aside>
    </div>
</div>
@endsection
