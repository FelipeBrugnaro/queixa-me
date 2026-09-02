<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Content\Models\Post;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        $companies = collect();
        $complaints = collect();
        $posts = collect();

        if (mb_strlen($term) >= 2) {
            $companies = Company::public()
                ->search($term)
                ->with('category:id,name,slug')
                ->orderByDesc('published_complaints_count')
                ->limit(8)
                ->get();

            $complaints = Complaint::published()
                ->search($term)
                ->forCards()
                ->latest('published_at')
                ->limit(8)
                ->get();

            $posts = Post::published()
                ->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')
                    ->orWhere('excerpt', 'like', '%'.$term.'%'))
                ->limit(4)
                ->get();
        }

        // Páginas de resultados de pesquisa nunca devem ser indexadas:
        // geram URLs ilimitados e duplicam conteúdo que já existe noutras
        // páginas com melhor estrutura.
        $this->seo()
            ->title($term !== '' ? 'Resultados para "'.$term.'"' : 'Pesquisar')
            ->description('Pesquisa empresas, reclamações e artigos no queixa.me.')
            ->canonical(route('search'))
            ->noindex(follow: true);

        $this->breadcrumbs([['label' => 'Pesquisar', 'url' => route('search')]]);

        return view('public.search', [
            'term' => $term,
            'companies' => $companies,
            'complaints' => $complaints,
            'posts' => $posts,
            'total' => $companies->count() + $complaints->count() + $posts->count(),
        ]);
    }
}
