{{--
    Metadados renderizados a partir do SeoManager do pedido.
    Nenhuma página precisa de repetir estas tags: descreve-se a si própria
    no controller e o resultado aparece aqui de forma consistente.
--}}
<title>{{ $seo->fullTitle() }}</title>
<meta name="description" content="{{ $seo->metaDescription() }}">
<meta name="robots" content="{{ $seo->robots() }}">
<link rel="canonical" href="{{ $seo->canonicalUrl() }}">

@if ($seo->prevUrl())
    <link rel="prev" href="{{ $seo->prevUrl() }}">
@endif
@if ($seo->nextUrl())
    <link rel="next" href="{{ $seo->nextUrl() }}">
@endif

<meta property="og:site_name" content="{{ config('queixame.brand.name') }}">
<meta property="og:locale" content="pt_PT">
<meta property="og:type" content="{{ $seo->ogType() }}">
<meta property="og:title" content="{{ $seo->fullTitle() }}">
<meta property="og:description" content="{{ $seo->metaDescription() }}">
<meta property="og:url" content="{{ $seo->canonicalUrl() }}">
<meta property="og:image" content="{{ $seo->imageUrl() }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

@if ($seo->publishedTime())
    <meta property="article:published_time" content="{{ $seo->publishedTime() }}">
@endif
@if ($seo->modifiedTime())
    <meta property="article:modified_time" content="{{ $seo->modifiedTime() }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->fullTitle() }}">
<meta name="twitter:description" content="{{ $seo->metaDescription() }}">
<meta name="twitter:image" content="{{ $seo->imageUrl() }}">

@foreach ($seo->allSchemas() as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach
