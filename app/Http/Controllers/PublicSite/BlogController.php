<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\Content\Models\Post;
use App\Domain\Content\Models\PostCategory;
use App\Domain\Content\Models\Tag;
use App\Domain\Seo\Services\SchemaBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $posts = Post::published()
            ->with(['category:id,name,slug', 'author:id,public_name,uuid,status'])
            ->latest('published_at')
            ->paginate(9);

        $page = (int) $request->query('page', '1');

        $this->seo()
            ->title('Notícias e direitos do consumidor'.($page > 1 ? ' — página '.$page : ''))
            ->description('Artigos sobre direitos do consumidor, compras online, encomendas, entregas, fraudes, atendimento e legislação.')
            ->canonical($request->url().($page > 1 ? '?page='.$page : ''))
            ->pagination($posts->previousPageUrl(), $posts->nextPageUrl());

        $this->breadcrumbs([['label' => 'Notícias', 'url' => route('blog.index')]]);

        return view('public.blog.index', [
            'posts' => $posts,
            'categories' => PostCategory::orderBy('position')->get(),
            'featured' => $page === 1
                ? Post::published()->where('is_featured', true)->latest('published_at')->first()
                : null,
        ]);
    }

    public function category(PostCategory $category): View
    {
        $posts = Post::published()
            ->where('category_id', $category->id)
            ->with('category:id,name,slug')
            ->latest('published_at')
            ->paginate(9);

        $this->seo()
            ->title($category->meta_title ?: $category->name)
            ->description($category->meta_description ?: $category->description ?: 'Artigos sobre '.mb_strtolower($category->name).' no queixa.me.')
            ->canonical(route('blog.category', $category))
            ->pagination($posts->previousPageUrl(), $posts->nextPageUrl());

        $this->breadcrumbs([
            ['label' => 'Notícias', 'url' => route('blog.index')],
            ['label' => $category->name, 'url' => route('blog.category', $category)],
        ]);

        return view('public.blog.index', [
            'posts' => $posts,
            'categories' => PostCategory::orderBy('position')->get(),
            'activeCategory' => $category,
            'featured' => null,
        ]);
    }

    public function tag(Tag $tag): View
    {
        $posts = $tag->posts()
            ->published()
            ->with('category:id,name,slug')
            ->latest('published_at')
            ->paginate(9);

        $this->seo()
            ->title('Artigos sobre '.$tag->name)
            ->description('Todos os artigos do queixa.me com a etiqueta '.$tag->name.'.')
            ->canonical(route('blog.tag', $tag))
            ->pagination($posts->previousPageUrl(), $posts->nextPageUrl());

        // Páginas de etiqueta com pouco conteúdo são thin content clássico.
        if ($posts->total() < 3) {
            $this->seo()->noindex(follow: true);
        }

        $this->breadcrumbs([
            ['label' => 'Notícias', 'url' => route('blog.index')],
            ['label' => $tag->name, 'url' => null],
        ]);

        return view('public.blog.index', [
            'posts' => $posts,
            'categories' => PostCategory::orderBy('position')->get(),
            'activeTag' => $tag,
            'featured' => null,
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->status->value === 'published' && $post->published_at?->isPast(), 404);

        $post->load(['category:id,name,slug', 'author:id,public_name,uuid,status,avatar_path', 'tags']);
        $post->incrementQuietly('views_count');

        $related = Post::published()
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->latest('published_at')
            ->limit(3)
            ->get();

        $this->seo()
            ->title($post->meta_title ?: $post->title)
            ->description($post->meta_description ?: $post->excerpt)
            ->canonical($post->canonical_url ?: $post->url())
            ->image($post->coverUrl())
            ->article(
                $post->published_at?->toIso8601String(),
                $post->updated_at?->toIso8601String(),
            )
            ->schema(SchemaBuilder::post($post));

        if (! $post->is_indexable) {
            $this->seo()->noindex();
        }

        $this->breadcrumbs([
            ['label' => 'Notícias', 'url' => route('blog.index')],
            ...($post->category ? [['label' => $post->category->name, 'url' => route('blog.category', $post->category)]] : []),
            ['label' => $post->title, 'url' => null],
        ]);

        return view('public.blog.show', [
            'post' => $post,
            'related' => $related,
        ]);
    }

    public function feed(): Response
    {
        $posts = Post::published()->latest('published_at')->limit(30)->get();

        return response()
            ->view('public.blog.feed', ['posts' => $posts])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
