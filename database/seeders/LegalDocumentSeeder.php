<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Models\LegalDocument;
use Illuminate\Database\Seeder;

/**
 * Versões iniciais dos documentos legais.
 *
 * AVISO IMPORTANTE: estes textos são um ponto de partida operacional, não
 * aconselhamento jurídico. Antes de o portal receber dados reais, devem ser
 * revistos por advogado — nomeadamente as secções de responsabilidade,
 * remoção de conteúdo e transferência de dados a terceiros.
 */
class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            [
                'key' => 'terms',
                'title' => 'Termos e Condições',
                'slug' => 'termos-e-condicoes',
                'version' => (string) config('queixame.legal.terms_version'),
                'meta_description' => 'Regras de utilização do queixa.me: o que é permitido publicar, direitos e deveres de consumidores e empresas.',
                'body' => $this->terms(),
            ],
            [
                'key' => 'privacy',
                'title' => 'Política de Privacidade',
                'slug' => 'politica-de-privacidade',
                'version' => (string) config('queixame.legal.privacy_version'),
                'meta_description' => 'Que dados pessoais o queixa.me recolhe, com que fundamento, durante quanto tempo e com quem são partilhados.',
                'body' => $this->privacy(),
            ],
            [
                'key' => 'data_protection',
                'title' => 'Política de Proteção de Dados',
                'slug' => 'protecao-de-dados',
                'version' => (string) config('queixame.legal.data_protection_version'),
                'meta_description' => 'Medidas técnicas e organizativas de proteção de dados no queixa.me e como exercer os teus direitos.',
                'body' => $this->dataProtection(),
            ],
            [
                'key' => 'moderation',
                'title' => 'Política de Moderação',
                'slug' => 'politica-de-moderacao',
                'version' => '1.0',
                'meta_description' => 'Critérios de aprovação, pedido de alterações, rejeição e remoção de reclamações no queixa.me.',
                'body' => $this->moderation(),
            ],
        ];

        foreach ($documents as $document) {
            LegalDocument::updateOrCreate(
                ['key' => $document['key'], 'version' => $document['version']],
                $document + ['is_current' => true, 'effective_from' => now()],
            );
        }
    }

    private function terms(): string
    {
        return <<<'HTML'
<h2>1. O que é o queixa.me</h2>
<p>O queixa.me é uma plataforma privada e independente onde consumidores publicam reclamações sobre empresas e onde as empresas podem responder publicamente.</p>
<p><strong>O queixa.me não é uma entidade oficial de resolução de conflitos.</strong> Não substitui o Livro de Reclamações, as entidades reguladoras sectoriais, os centros de arbitragem, os organismos de resolução alternativa de litígios (RAL) nem os tribunais. A publicação de uma reclamação não interrompe nem suspende prazos legais.</p>

<h2>2. Contas</h2>
<p>Para publicar uma reclamação é necessário criar conta com dados verdadeiros e ter pelo menos 16 anos. Cada pessoa pode ter apenas uma conta de consumidor. É proibido criar contas em nome de terceiros.</p>
<p>O nome público é a identidade visível na plataforma. Não pode imitar o queixa.me, entidades oficiais, marcas ou outras pessoas.</p>

<h2>3. Conteúdo publicado</h2>
<p>Ao publicares uma reclamação, declaras que:</p>
<ul>
<li>a situação descrita é verdadeira e ocorreu efetivamente contigo;</li>
<li>o conteúdo não contém dados pessoais de terceiros;</li>
<li>não usas linguagem ofensiva, discriminatória ou ameaçadora;</li>
<li>não imputas a prática de crimes que não consigas sustentar.</li>
</ul>
<p>Mantens a titularidade do teu conteúdo e concedes ao queixa.me uma licença não exclusiva para o publicar, reproduzir e indexar no âmbito do funcionamento da plataforma.</p>

<h2>4. Moderação</h2>
<p>Todas as reclamações são analisadas antes da publicação. O queixa.me pode aprovar, pedir alterações, rejeitar ou remover conteúdo, de acordo com a Política de Moderação. As decisões são comunicadas com o respetivo motivo.</p>

<h2>5. Direito de resposta</h2>
<p>A empresa visada tem sempre direito a responder publicamente. A resposta é publicada no mesmo local e com o mesmo destaque da reclamação.</p>

<h2>6. Índices e classificações</h2>
<p>Os índices e rankings resultam da aplicação de uma metodologia pública e uniforme a dados de comportamento observável (respostas, resoluções, tempos e avaliações). Não constituem juízo sobre a qualidade dos produtos ou serviços de uma empresa e não são recomendação de compra.</p>

<h2>7. Responsabilidade</h2>
<p>O conteúdo das reclamações é da responsabilidade de quem o publica. O queixa.me atua como prestador de serviço de alojamento e remove conteúdo ilícito assim que dele toma conhecimento efetivo, nos termos da lei aplicável.</p>

<h2>8. Contas de empresa</h2>
<p>O acesso a uma ficha de empresa exige validação da ligação à marca. O uso indevido de uma conta de empresa — nomeadamente a publicação de reclamações falsas contra concorrentes ou a pressão sobre consumidores — implica suspensão imediata.</p>

<h2>9. Alterações</h2>
<p>Estes termos podem ser alterados. Alterações materiais são comunicadas e exigem nova aceitação. A versão em vigor e a data de entrada em vigor constam sempre desta página.</p>

<h2>10. Lei aplicável</h2>
<p>Aplica-se a lei portuguesa.</p>
HTML;
    }

    private function privacy(): string
    {
        return <<<'HTML'
<h2>1. Responsável pelo tratamento</h2>
<p>O responsável pelo tratamento dos dados pessoais recolhidos nesta plataforma é a entidade que opera o queixa.me. Para qualquer questão relativa a dados pessoais, escreve para <a href="mailto:privacidade@queixa.me">privacidade@queixa.me</a>.</p>

<h2>2. Que dados recolhemos</h2>
<ul>
<li><strong>Dados de conta:</strong> nome público, nome próprio, apelido, data de nascimento, género, email e, opcionalmente, telefone e localidade.</li>
<li><strong>Dados da reclamação:</strong> o texto que escreves, os anexos que juntas e os dados de contacto que confirmas no último passo.</li>
<li><strong>Dados técnicos:</strong> endereço IP, agente do navegador e registos de acesso, usados para segurança e prevenção de abuso.</li>
</ul>

<h2>3. Com que fundamento</h2>
<ul>
<li><strong>Execução do serviço</strong> (art. 6.º, n.º 1, al. b): criação de conta, publicação e acompanhamento de reclamações.</li>
<li><strong>Consentimento</strong> (al. a): transmissão dos teus dados de contacto à empresa visada e envio de comunicações de marketing. Ambos são independentes e podem ser recusados.</li>
<li><strong>Interesse legítimo</strong> (al. f): segurança da plataforma, prevenção de fraude e abuso, e manutenção de um arquivo público de reclamações e respostas.</li>
<li><strong>Obrigação legal</strong> (al. c): conservação de registos quando a lei o exija.</li>
</ul>

<h2>4. O que é público e o que não é</h2>
<p>São públicos: o nome público (ou "anónimo", se assim o escolheres), o texto da reclamação, a categoria, o distrito, as datas e as respostas da empresa.</p>
<p><strong>Nunca são públicos:</strong> o nome civil, o email, o telefone, a morada, a data de nascimento, os anexos e o conteúdo das mensagens privadas.</p>

<h2>5. Partilha com a empresa visada</h2>
<p>Quando submetes uma reclamação e dás o consentimento específico para isso, transmitimos à empresa visada os teus dados de contacto e os elementos do processo. Sem esse consentimento a reclamação não pode seguir, porque a empresa não teria como identificar o teu caso. Este consentimento é registado com a data, hora e versão dos documentos aceites.</p>

<h2>6. Quanto tempo conservamos</h2>
<ul>
<li><strong>Conta:</strong> enquanto estiver ativa.</li>
<li><strong>Dados de contacto associados a uma reclamação:</strong> até 2 anos após o desfecho, findos os quais são apagados automaticamente.</li>
<li><strong>Reclamações publicadas:</strong> por tempo indeterminado, enquanto conteúdo público de interesse informativo. Após a eliminação da conta ficam sem qualquer ligação a ti.</li>
<li><strong>Registos técnicos:</strong> 12 meses.</li>
</ul>

<h2>7. Os teus direitos</h2>
<p>Tens direito de acesso, retificação, apagamento, limitação, portabilidade e oposição, e podes retirar o consentimento a qualquer momento sem afetar a licitude do tratamento anterior. Podes exercer estes direitos na tua área pessoal, em "Privacidade", ou por email.</p>
<p>Se considerares que os teus direitos não foram respeitados, podes reclamar junto da Comissão Nacional de Proteção de Dados (CNPD).</p>

<h2>8. Segurança</h2>
<p>As palavras-passe são guardadas com algoritmos de derivação resistentes. Os dados de contacto associados a reclamações são cifrados em repouso. Os anexos ficam em armazenamento privado e são servidos apenas a quem tem autorização.</p>
HTML;
    }

    private function dataProtection(): string
    {
        return <<<'HTML'
<h2>Princípios</h2>
<p>A proteção de dados no queixa.me foi desenhada desde a arquitetura, e não acrescentada depois. Os princípios que seguimos:</p>
<ul>
<li><strong>Minimização:</strong> não pedimos dados que não sejam necessários para o fim declarado.</li>
<li><strong>Separação:</strong> os dados que identificam a pessoa vivem separados do conteúdo público, em tabela própria e cifrada.</li>
<li><strong>Consentimento granular:</strong> aceitar os termos, autorizar a transmissão à empresa e receber marketing são decisões distintas.</li>
<li><strong>Prova:</strong> cada consentimento é registado com tipo, versão do documento, data, hora, IP e agente do navegador.</li>
</ul>

<h2>Medidas técnicas</h2>
<ul>
<li>Cifra em trânsito (HTTPS obrigatório) e cifra em repouso dos dados de contacto.</li>
<li>Anexos em armazenamento privado, servidos apenas mediante verificação de permissões.</li>
<li>Remoção automática de metadados EXIF das imagens carregadas, incluindo coordenadas GPS.</li>
<li>Deteção automática de dados sensíveis (IBAN, NIF, cartões, contactos) no texto submetido, com aviso ao autor e revisão prioritária.</li>
<li>Registo de auditoria de todas as ações de moderação e administração.</li>
<li>Limitação de tentativas de autenticação e alteração de email em duas fases com aviso ao endereço anterior.</li>
</ul>

<h2>Medidas organizativas</h2>
<ul>
<li>Acesso a dados pessoais limitado a quem deles precisa para moderar ou tratar um pedido.</li>
<li>Pedidos de titulares tratados no prazo máximo de 30 dias.</li>
<li>Procedimento de notificação de violação de dados em 72 horas.</li>
</ul>

<h2>Eliminação de conta</h2>
<p>A eliminação da conta é executada por anonimização: apagamos tudo o que te identifica e mantemos o texto público das reclamações e as respostas das empresas, sem qualquer ligação a ti. Esta opção equilibra o direito ao apagamento com o interesse público num arquivo de reclamações que não possa ser manipulado seletivamente.</p>
HTML;
    }

    private function moderation(): string
    {
        return <<<'HTML'
<h2>O que analisamos</h2>
<p>Todas as reclamações são revistas por uma pessoa antes de serem publicadas. A análise verifica:</p>
<ul>
<li>se o texto não expõe dados pessoais teus ou de terceiros (nomes de funcionários, moradas, NIF, IBAN, matrículas, números de documento);</li>
<li>se a linguagem não é ofensiva, discriminatória ou ameaçadora;</li>
<li>se existem elementos suficientes para a empresa poder identificar e tratar o caso;</li>
<li>se a entidade visada corresponde efetivamente à responsável;</li>
<li>se não se trata de duplicado, spam ou conteúdo promocional.</li>
</ul>

<h2>O que não fazemos</h2>
<p>Não avaliamos quem tem razão. A moderação verifica a forma e a legalidade do conteúdo, não o mérito da reclamação — esse é o objeto do diálogo público entre consumidor e empresa.</p>
<p>Não removemos reclamações por serem desfavoráveis a uma empresa. O direito de resposta existe exatamente para esse efeito.</p>

<h2>Decisões possíveis</h2>
<ul>
<li><strong>Aprovada:</strong> a reclamação é publicada e a empresa é notificada.</li>
<li><strong>Necessita de alterações:</strong> devolvemos com o motivo concreto e o que deves corrigir. Depois de editares, volta à fila.</li>
<li><strong>Rejeitada:</strong> quando o conteúdo não pode ser publicado em nenhuma versão razoável.</li>
<li><strong>Removida:</strong> conteúdo já publicado que se veio a revelar ilícito ou que violou as regras.</li>
</ul>

<h2>Reclamações abusivas</h2>
<p>Bloqueamos contas que publiquem reclamações falsas, campanhas coordenadas contra uma empresa ou reclamações em nome de terceiros. Reclamações submetidas por concorrentes são removidas e a conta empresarial associada é suspensa.</p>

<h2>Prioridade</h2>
<p>Reclamações onde o sistema deteta dados sensíveis são analisadas primeiro, independentemente da ordem de chegada, porque o risco de exposição é imediato.</p>
HTML;
    }
}
