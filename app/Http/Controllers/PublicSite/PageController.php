<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\Content\Models\FaqCategory;
use App\Domain\Content\Models\LegalDocument;
use App\Domain\Seo\Services\SchemaBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    public function about(): View
    {
        $this->seo()
            ->title('Sobre o queixa.me')
            ->description('O queixa.me é um portal independente onde consumidores publicam reclamações e as empresas respondem publicamente. Sabe o que somos, o que não somos e como nos financiamos.')
            ->canonical(route('about'));

        $this->breadcrumbs([['label' => 'Sobre nós', 'url' => route('about')]]);

        return view('public.pages.about');
    }

    public function howItWorks(): View
    {
        $this->seo()
            ->title('Como funciona o queixa.me')
            ->description('Do momento em que escreves a reclamação até à confirmação de que o problema ficou resolvido: todos os passos, prazos e estados explicados.')
            ->canonical(route('how-it-works'));

        $this->breadcrumbs([['label' => 'Como funciona', 'url' => route('how-it-works')]]);

        return view('public.pages.how-it-works', [
            'slaDays' => (int) config('queixame.complaints.response_sla_days'),
            'confirmDays' => (int) config('queixame.complaints.resolution_confirmation_days'),
            'autoCloseDays' => (int) config('queixame.complaints.auto_close_days'),
        ]);
    }

    /**
     * Página de metodologia.
     *
     * Os valores vêm da mesma configuração que alimenta o cálculo real. Uma
     * página de metodologia escrita à mão fica desatualizada ao primeiro
     * ajuste de pesos — e uma metodologia desatualizada é pior do que não ter
     * metodologia publicada, porque é uma afirmação falsa.
     */
    public function methodology(): View
    {
        $this->seo()
            ->title('Como calculamos os índices de satisfação')
            ->description('A metodologia completa do queixa.me: pesos de cada componente, correção estatística para empresas com poucas reclamações, janela temporal e tratamento de reclamações abusivas.')
            ->canonical(route('methodology'));

        $this->breadcrumbs([['label' => 'Índices de satisfação', 'url' => route('methodology')]]);

        return view('public.pages.methodology', [
            'weights' => (array) config('queixame.index.weights'),
            'priorWeight' => (int) config('queixame.index.bayesian_prior_weight'),
            'minimum' => (int) config('queixame.index.ranking_minimum_complaints'),
            'speedBest' => (int) config('queixame.index.speed_best_hours'),
            'speedWorst' => (int) config('queixame.index.speed_worst_hours'),
            'slaDays' => (int) config('queixame.complaints.response_sla_days'),
        ]);
    }

    public function faq(Request $request): View
    {
        $audience = $request->query('publico') === 'empresas' ? 'business' : 'consumer';

        $categories = FaqCategory::with([
            'items' => fn ($q) => $q->where('is_published', true)
                ->whereIn('audience', [$audience, 'all'])
                ->orderBy('position'),
        ])->orderBy('position')->get()->filter(fn ($c) => $c->items->isNotEmpty());

        $allItems = $categories->flatMap->items;

        $this->seo()
            ->title('Perguntas frequentes')
            ->description('Respostas às dúvidas mais comuns sobre reclamar, moderação, anonimato, dados pessoais, ranking e contas de empresa.')
            ->canonical(route('faq'))
            ->schema(SchemaBuilder::faq($allItems));

        if ($audience === 'business') {
            $this->seo()->noindex(follow: true);
        }

        $this->breadcrumbs([['label' => 'Perguntas frequentes', 'url' => route('faq')]]);

        return view('public.pages.faq', [
            'categories' => $categories,
            'audience' => $audience,
        ]);
    }

    public function contact(): View
    {
        $this->seo()
            ->title('Contactos')
            ->description('Fala com a equipa do queixa.me. Para reclamações sobre empresas usa o formulário de reclamação; este canal é para assuntos relacionados com o portal.')
            ->canonical(route('contact'));

        $this->breadcrumbs([['label' => 'Contactos', 'url' => route('contact')]]);

        return view('public.pages.contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc,dns', 'max:190'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:20', 'max:4000'],
            // Campo isco: preenchido apenas por robôs.
            'website' => ['nullable', 'size:0'],
        ], [
            'website.size' => 'Pedido inválido.',
        ]);

        Log::channel('single')->info('Contacto recebido', [
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
        ]);

        return back()->with('success', 'Mensagem enviada. Respondemos normalmente em 2 dias úteis.');
    }

    public function terms(): View
    {
        return $this->legal('terms', 'Termos e Condições', route('legal.terms'));
    }

    public function privacy(): View
    {
        return $this->legal('privacy', 'Política de Privacidade', route('legal.privacy'));
    }

    public function dataProtection(): View
    {
        return $this->legal('data_protection', 'Política de Proteção de Dados', route('legal.data-protection'));
    }

    public function moderation(): View
    {
        return $this->legal('moderation', 'Política de Moderação', route('legal.moderation'));
    }

    private function legal(string $key, string $fallbackTitle, string $canonical): View
    {
        $document = LegalDocument::current($key);

        $this->seo()
            ->title($document?->title ?: $fallbackTitle)
            ->description($document?->meta_description ?: $fallbackTitle.' do queixa.me.')
            ->canonical($canonical);

        $this->breadcrumbs([['label' => $document?->title ?: $fallbackTitle, 'url' => $canonical]]);

        return view('public.pages.legal', [
            'document' => $document,
            'fallbackTitle' => $fallbackTitle,
            'documentKey' => $key,
        ]);
    }
}
