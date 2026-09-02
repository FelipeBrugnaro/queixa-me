<?php

declare(strict_types=1);

use App\Http\Controllers\PublicSite\AttachmentController;
use App\Http\Controllers\PublicSite\AwardsController;
use App\Http\Controllers\PublicSite\BlogController;
use App\Http\Controllers\PublicSite\CompanyController;
use App\Http\Controllers\PublicSite\CompareController;
use App\Http\Controllers\PublicSite\ComplaintController;
use App\Http\Controllers\PublicSite\HomeController;
use App\Http\Controllers\PublicSite\PageController;
use App\Http\Controllers\PublicSite\RankingController;
use App\Http\Controllers\PublicSite\SearchController;
use App\Http\Controllers\PublicSite\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas públicas
|--------------------------------------------------------------------------
|
| URLs em português, estáveis e legíveis. Cada entidade pública tem um URL
| canónico próprio e permanente, porque é dele que depende a indexação:
|
|   /empresa/{slug}
|   /reclamacao/{slug}
|   /noticias/{slug}
|
| Alterações de slug nunca partem ligações: passam pela tabela de
| redirecionamentos (ver HandleRedirects middleware).
*/

Route::get('/', HomeController::class)->name('home');

// --- Reclamações -----------------------------------------------------
Route::get('/reclamacoes', [ComplaintController::class, 'index'])->name('complaints.index');
Route::get('/reclamacao/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');

// --- Empresas --------------------------------------------------------
Route::get('/empresas', [CompanyController::class, 'index'])->name('companies.index');
Route::get('/empresas/categoria/{category:slug}', [CompanyController::class, 'category'])->name('companies.category');
Route::get('/empresa/{company}', [CompanyController::class, 'show'])->name('companies.show');
Route::get('/empresa/{company}/reclamacoes', [CompanyController::class, 'complaints'])->name('companies.complaints');
Route::get('/api/empresas/sugestoes', [CompanyController::class, 'suggest'])
    ->middleware('throttle:60,1')
    ->name('companies.suggest');

// --- Rankings e comparação -------------------------------------------
Route::get('/ranking', RankingController::class)->name('ranking');
Route::get('/comparar', [CompareController::class, 'index'])->name('compare');
Route::get('/comparar/resultado', [CompareController::class, 'show'])->name('compare.show');
Route::get('/marcas-do-mes', [AwardsController::class, 'index'])->name('awards');
Route::get('/marcas-do-mes/{period}', [AwardsController::class, 'period'])
    ->where('period', '[0-9]{4}-[0-9]{2}')
    ->name('awards.period');

// --- Conteúdos -------------------------------------------------------
Route::get('/noticias', [BlogController::class, 'index'])->name('blog.index');
Route::get('/noticias/feed', [BlogController::class, 'feed'])->name('blog.feed');
Route::get('/noticias/categoria/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/noticias/tag/{tag:slug}', [BlogController::class, 'tag'])->name('blog.tag');
Route::get('/noticias/{post}', [BlogController::class, 'show'])->name('blog.show');

// --- Páginas institucionais ------------------------------------------
Route::get('/sobre-nos', [PageController::class, 'about'])->name('about');
Route::get('/como-funciona', [PageController::class, 'howItWorks'])->name('how-it-works');
Route::get('/indices-de-satisfacao', [PageController::class, 'methodology'])->name('methodology');
Route::get('/perguntas-frequentes', [PageController::class, 'faq'])->name('faq');
Route::get('/contactos', [PageController::class, 'contact'])->name('contact');
Route::post('/contactos', [PageController::class, 'submitContact'])
    ->middleware('throttle:5,10')
    ->name('contact.submit');

Route::get('/termos-e-condicoes', [PageController::class, 'terms'])->name('legal.terms');
Route::get('/politica-de-privacidade', [PageController::class, 'privacy'])->name('legal.privacy');
Route::get('/protecao-de-dados', [PageController::class, 'dataProtection'])->name('legal.data-protection');
Route::get('/politica-de-moderacao', [PageController::class, 'moderation'])->name('legal.moderation');

// --- Pesquisa transversal --------------------------------------------
Route::get('/pesquisar', SearchController::class)->name('search');

// --- Anexos (sempre autorizados, nunca servidos do disco público) ----
Route::get('/anexo/{uuid}', AttachmentController::class)
    ->middleware('auth')
    ->name('attachments.show');

// --- SEO técnico ------------------------------------------------------
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-{type}-{page}.xml', [SitemapController::class, 'chunk'])
    ->where(['type' => '[a-z]+', 'page' => '[0-9]+'])
    ->name('sitemap.chunk');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

require __DIR__.'/auth.php';
require __DIR__.'/consumer.php';
require __DIR__.'/business.php';
require __DIR__.'/admin.php';
