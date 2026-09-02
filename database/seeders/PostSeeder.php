<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Accounts\Models\User;
use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\Models\Post;
use App\Domain\Content\Models\PostCategory;
use App\Domain\Content\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['Direitos do consumidor', 'Os teus direitos explicados sem juridiquês.'],
            ['Compras online', 'Comprar à distância com segurança.'],
            ['Encomendas e entregas', 'Prazos, atrasos, extravios e o que podes exigir.'],
            ['Fraudes e burlas', 'Como reconhecer e o que fazer quando acontece.'],
            ['Atendimento ao cliente', 'O que distingue um bom apoio ao cliente.'],
            ['Legislação', 'Alterações legais que te afetam enquanto consumidor.'],
        ])->mapWithKeys(function (array $data, int $position) {
            $category = PostCategory::updateOrCreate(
                ['slug' => Str::slug($data[0])],
                ['name' => $data[0], 'description' => $data[1], 'position' => $position],
            );

            return [$category->slug => $category];
        });

        $author = User::where('email', 'admin@queixa.me')->first();

        $posts = [
            [
                'Devolução de compras online: o que a lei te garante',
                'direitos-do-consumidor',
                'Tens 14 dias para mudares de ideias numa compra online, sem justificar. Explicamos quando o prazo começa, o que está fora e quem paga o portes de devolução.',
                ['devolucao', 'compras-online', 'direito-de-livre-resolucao'],
            ],
            [
                'A tua encomenda está atrasada. Quais são os teus direitos?',
                'encomendas-e-entregas',
                'Atraso não é o mesmo que incumprimento. Vê a partir de que momento podes exigir a entrega, cancelar a compra ou pedir o reembolso integral.',
                ['encomendas', 'entregas', 'reembolso'],
            ],
            [
                'Garantia de 3 anos: o que cobre e o que não cobre',
                'direitos-do-consumidor',
                'A garantia legal aplica-se a defeitos de conformidade, não a mau uso. Percebe a diferença, e porque é que a loja não pode remeter-te sempre para a marca.',
                ['garantia', 'reparacao', 'bens-defeituosos'],
            ],
            [
                'Como reconhecer uma loja online falsa antes de pagar',
                'fraudes-e-burlas',
                'Preços bons demais, ausência de NIF, contactos genéricos e métodos de pagamento sem proteção: os sinais que se repetem em quase todos os casos.',
                ['fraude', 'compras-online', 'seguranca'],
            ],
            [
                'Cancelei o contrato e continuo a ser cobrado. E agora?',
                'direitos-do-consumidor',
                'O passo a passo para provar o cancelamento, travar as cobranças e reaver o que foi cobrado indevidamente.',
                ['contratos', 'cobrancas', 'telecomunicacoes'],
            ],
            [
                'O que faz uma boa resposta a uma reclamação',
                'atendimento-ao-cliente',
                'Analisámos milhares de respostas de empresas. As que resolvem têm três coisas em comum — e nenhuma delas é pedir desculpa.',
                ['atendimento', 'empresas', 'boas-praticas'],
            ],
        ];

        foreach ($posts as $index => [$title, $categorySlug, $excerpt, $tags]) {
            $post = Post::updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'uuid' => (string) Str::uuid(),
                    'category_id' => $categories[$categorySlug]->id ?? null,
                    'author_id' => $author?->id,
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'body' => $this->body($title, $excerpt),
                    'status' => PostStatus::Published,
                    'published_at' => now()->subDays(($index + 1) * 9),
                    'is_featured' => $index === 0,
                    'meta_description' => $excerpt,
                ],
            );

            $post->tags()->sync(
                collect($tags)->map(fn (string $tag) => Tag::firstOrCreate(
                    ['slug' => Str::slug($tag)],
                    ['name' => Str::headline(str_replace('-', ' ', $tag))],
                )->id)->all()
            );
        }
    }

    private function body(string $title, string $excerpt): string
    {
        return <<<HTML
<p>{$excerpt}</p>

<h2>O essencial em três pontos</h2>
<ul>
<li>Guarda sempre prova escrita: emails, referências de encomenda e capturas de ecrã valem mais do que qualquer chamada telefónica.</li>
<li>Contacta primeiro a empresa pelo canal oficial e dá-lhe um prazo razoável para responder.</li>
<li>Se não houver resposta, publica a reclamação e acompanha o processo até ao fim.</li>
</ul>

<h2>Porque é que isto importa</h2>
<p>A maioria dos problemas de consumo resolve-se quando existe registo e visibilidade. Uma reclamação escrita, concreta e datada muda a forma como é tratada internamente numa empresa — deixa de ser uma queixa vaga e passa a ser um processo com prazo.</p>

<h2>O que podes fazer hoje</h2>
<p>Reúne as datas, os valores e a correspondência que já trocaste. Descreve o que aconteceu por ordem cronológica e indica claramente o que pretendes: reembolso, substituição, reparação ou correção de fatura. Quanto mais concreto for o pedido, mais depressa a empresa consegue responder-lhe.</p>

<p><em>Este artigo tem fins informativos e não constitui aconselhamento jurídico.</em></p>
HTML;
    }
}
