@inject('seo', 'App\Domain\Seo\Services\SeoManager')
<!DOCTYPE html>
<html lang="pt-PT" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4f46e5">

    @include('partials.seo')

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate" type="application/rss+xml" title="Notícias {{ config('queixame.brand.name') }}" href="{{ route('blog.feed') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-full flex-col">
    <a href="#conteudo" class="skip-link">Saltar para o conteúdo</a>

    @include('partials.header')

    @if ($seo->breadcrumbItems() && ! ($hideBreadcrumbs ?? false))
        <nav aria-label="Caminho de navegação" class="border-b border-ink-200/60 bg-white">
            <div class="container-page">
                <ol class="flex flex-wrap items-center gap-1 py-3 text-xs text-ink-500">
                    @foreach ($seo->breadcrumbItems() as $index => $crumb)
                        <li class="flex items-center gap-1">
                            @if ($index > 0)
                                <svg class="size-3 text-ink-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M7.05 4.55a.75.75 0 0 1 1.06 0l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06L10.94 10 7.05 6.11a.75.75 0 0 1 0-1.06Z"/>
                                </svg>
                            @endif

                            @if (! empty($crumb['url']) && ! $loop->last)
                                <a href="{{ $crumb['url'] }}" class="hover:text-ink-800">{{ $crumb['label'] }}</a>
                            @else
                                <span class="font-medium text-ink-700" @if($loop->last) aria-current="page" @endif>{{ $crumb['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </nav>
    @endif

    <main id="conteudo" class="flex-1">
        @include('partials.flash')
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
