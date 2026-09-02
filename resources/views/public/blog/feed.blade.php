<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ config('queixame.brand.name') }} — Notícias</title>
    <link>{{ route('blog.index') }}</link>
    <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml" />
    <description>Direitos do consumidor, compras online, encomendas, fraudes e atendimento.</description>
    <language>pt-PT</language>
    <lastBuildDate>{{ $posts->first()?->published_at?->toRfc2822String() ?? now()->toRfc2822String() }}</lastBuildDate>
    @foreach ($posts as $post)
    <item>
        <title>{{ $post->title }}</title>
        <link>{{ $post->url() }}</link>
        <guid isPermaLink="true">{{ $post->url() }}</guid>
        <description>{{ $post->excerpt }}</description>
        <pubDate>{{ $post->published_at?->toRfc2822String() }}</pubDate>
    </item>
    @endforeach
</channel>
</rss>
