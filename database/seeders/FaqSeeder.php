<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Models\FaqCategory;
use App\Domain\Content\Models\FaqItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Começar' => [
                [
                    'O que é o queixa.me?',
                    'O queixa.me é um portal independente onde consumidores publicam reclamações sobre empresas e as empresas têm a oportunidade de responder publicamente. Não somos uma entidade oficial de resolução de litígios nem substituímos o Livro de Reclamações, as entidades reguladoras ou os organismos de resolução alternativa de litígios (RAL). O que fazemos é dar visibilidade ao problema e medir, de forma transparente, quem responde e quem resolve.',
                    'all',
                ],
                [
                    'Como posso apresentar uma reclamação?',
                    'Cria conta, clica em "Reclamar" e segue os quatro passos: identificar a empresa, descrever o que aconteceu, acrescentar os detalhes e comprovativos, e confirmar os teus dados. O rascunho fica gravado, por isso podes sair e continuar mais tarde.',
                    'consumer',
                ],
                [
                    'Reclamar tem custos?',
                    'Não. Apresentar e acompanhar reclamações é gratuito para os consumidores.',
                    'consumer',
                ],
            ],
            'Publicação e moderação' => [
                [
                    'A reclamação é publicada imediatamente?',
                    'Não. Todas as reclamações passam por análise humana antes de serem publicadas. Verificamos se o texto não expõe dados pessoais (teus ou de terceiros), se não contém linguagem ofensiva e se tem elementos suficientes para a empresa poder responder. Normalmente demora menos de 48 horas.',
                    'all',
                ],
                [
                    'A minha reclamação foi devolvida para alterações. Porquê?',
                    'Quando faltam elementos essenciais ou o texto contém algo que não pode ser publicado, devolvemos a reclamação com o motivo concreto e uma indicação do que corrigir. Depois de editares, volta a entrar na fila.',
                    'consumer',
                ],
                [
                    'Posso corrigir uma reclamação já publicada?',
                    'Podes acrescentar atualizações ao fio da reclamação. O texto original mantém-se, porque alterá-lo depois de a empresa ter respondido tornaria a resposta incompreensível. Se houver um erro factual grave, contacta-nos.',
                    'consumer',
                ],
                [
                    'Como posso denunciar uma reclamação abusiva?',
                    'Qualquer pessoa pode denunciar conteúdo através do botão na página da reclamação. As empresas visadas têm um canal próprio de denúncia com fundamentação. Todas as denúncias são analisadas por uma pessoa.',
                    'all',
                ],
            ],
            'Privacidade e dados' => [
                [
                    'Posso reclamar anonimamente?',
                    'Perante o público, sim: podes optar por não mostrar o teu nome público, e nesse caso a reclamação aparece como anónima. Perante a empresa visada, não — a empresa recebe os teus dados de contacto, porque sem eles não consegue identificar o teu processo nem resolver o problema. Essa transmissão só acontece com o teu consentimento explícito, dado no último passo.',
                    'all',
                ],
                [
                    'Os meus dados pessoais ficam públicos?',
                    'Não. O único nome visível é o teu nome público, que escolhes e que pode ser diferente do teu nome real. O nome civil, o email, o telefone e a morada nunca aparecem nas páginas públicas. Os anexos também são privados por omissão.',
                    'all',
                ],
                [
                    'Como posso eliminar a minha conta?',
                    'Em "Privacidade" na tua área pessoal. O pedido é tratado no prazo legal de 30 dias. Eliminamos os teus dados pessoais e a ligação entre ti e as reclamações; o texto das reclamações já publicadas e as respostas das empresas mantêm-se, sem qualquer identificação tua. Isto protege a integridade do arquivo público e impede que o histórico seja apagado seletivamente.',
                    'all',
                ],
                [
                    'Posso pedir uma cópia dos meus dados?',
                    'Sim. Em "Privacidade" podes pedir a exportação de todos os dados que temos sobre ti.',
                    'all',
                ],
            ],
            'Empresas' => [
                [
                    'A empresa pode responder?',
                    'Sim, e é isso que queremos. Qualquer empresa pode reivindicar a sua ficha gratuitamente, receber as reclamações e responder publicamente ou em privado. Responder e resolver melhora o índice da empresa; ignorar piora-o.',
                    'all',
                ],
                [
                    'Como uma empresa cria o seu perfil?',
                    'Cria uma conta de empresa e faz o pedido de associação à ficha. Validamos a ligação à marca, normalmente em 1 a 2 dias úteis. Um email no domínio oficial da empresa acelera bastante o processo.',
                    'business',
                ],
                [
                    'A empresa pode marcar uma reclamação como resolvida?',
                    'Não. A empresa propõe uma solução; só o consumidor confirma que o problema ficou resolvido. Se fosse a empresa a fechar os seus próprios processos, a taxa de resolução deixaria de medir resolução.',
                    'all',
                ],
                [
                    'Podemos pedir a remoção de uma reclamação?',
                    'Podes denunciar conteúdo que viole as regras (dados pessoais expostos, informação comprovadamente falsa, linguagem ofensiva) e analisamos o caso. Não removemos reclamações apenas por serem desfavoráveis — o direito de resposta existe precisamente para isso.',
                    'business',
                ],
            ],
            'Índices e ranking' => [
                [
                    'Como funciona o ranking?',
                    'O ranking ordena as empresas pelo índice de satisfação, nunca pelo número de reclamações. Uma empresa grande recebe naturalmente mais reclamações do que uma pequena, e isso não a torna pior. O que medimos é comportamento: responde? resolve? em quanto tempo? com que satisfação de quem reclamou?',
                    'all',
                ],
                [
                    'Como é calculado o índice de satisfação?',
                    'Combina quatro componentes — taxa de resposta, taxa de resolução confirmada, avaliação dos consumidores e rapidez da primeira resposta — sobre os últimos 12 meses, com uma correção estatística que impede que empresas com muito poucas reclamações apareçam artificialmente no topo ou no fundo. A fórmula completa está publicada na página de metodologia.',
                    'all',
                ],
                [
                    'Uma empresa com poucas reclamações aparece no ranking?',
                    'Só a partir de um número mínimo de reclamações na janela de 12 meses. Abaixo disso mostramos "dados insuficientes", porque qualquer percentagem calculada sobre duas ou três reclamações seria ruído, não informação.',
                    'all',
                ],
            ],
        ];

        $position = 0;

        foreach ($data as $categoryName => $items) {
            $category = FaqCategory::updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'position' => $position],
            );

            foreach ($items as $itemPosition => [$question, $answer, $audience]) {
                FaqItem::updateOrCreate(
                    ['slug' => Str::slug(Str::limit($question, 80, ''))],
                    [
                        'category_id' => $category->id,
                        'question' => $question,
                        'answer' => $answer,
                        'audience' => $audience,
                        'position' => $itemPosition,
                        'is_published' => true,
                    ],
                );
            }

            $position++;
        }
    }
}
