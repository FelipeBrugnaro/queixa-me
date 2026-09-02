@extends('layouts.app')

@php
    $labels = [
        'response_rate' => ['Taxa de resposta', 'A empresa respondeu à reclamação?'],
        'resolution_rate' => ['Taxa de resolução', 'O consumidor confirmou que o problema ficou resolvido?'],
        'satisfaction' => ['Avaliação dos consumidores', 'Que nota deu quem passou pelo processo?'],
        'speed' => ['Rapidez da resposta', 'Quanto tempo demorou a primeira resposta?'],
    ];
@endphp

@section('content')
<div class="container-page py-8">
    <div class="lg:grid lg:grid-cols-[1fr_16rem] lg:gap-12">

        <article class="min-w-0 max-w-3xl">
            <header>
                <h1 class="text-3xl font-bold sm:text-4xl">Como calculamos os índices</h1>
                <p class="mt-4 text-lg leading-relaxed text-ink-600">
                    Um ranking só é útil se for possível verificá-lo. Esta página descreve exatamente
                    o que medimos, com que pesos e com que correções — e porque é que cada escolha
                    foi feita assim.
                </p>
            </header>

            <div class="prose-qm mt-10">

                <h2 id="problema">O problema que estamos a resolver</h2>
                <p>
                    A forma mais óbvia de ordenar empresas num portal de reclamações seria pelo número
                    de reclamações recebidas. É também a forma mais enganadora: um retalhista com
                    milhões de clientes receberá sempre mais reclamações do que uma loja de bairro,
                    ainda que sirva melhor cada cliente. Uma lista ordenada por volume mede dimensão,
                    não qualidade.
                </p>
                <p>
                    A segunda tentação seria usar médias simples. Também não funciona: uma empresa com
                    uma única reclamação resolvida teria 100% de taxa de resolução e ficaria à frente
                    de outra com quatro mil reclamações e 92% — um número que representa muito mais
                    trabalho e muito mais informação.
                </p>
                <p>
                    O queixa.me mede <strong>comportamento perante reclamações</strong>: responde,
                    resolve, em quanto tempo e com que satisfação de quem reclamou. Todas as
                    componentes são taxas, nunca contagens.
                </p>

                <h2 id="componentes">As quatro componentes</h2>
                <p>O índice de satisfação (0 a 100) combina quatro medidas independentes:</p>
            </div>

            {{-- Pesos: lidos da mesma configuração que alimenta o cálculo real --}}
            <div class="mt-6 space-y-4">
                @foreach ($weights as $key => $weight)
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-baseline justify-between gap-4">
                                <h3 class="font-semibold">{{ $labels[$key][0] ?? $key }}</h3>
                                <span class="shrink-0 text-sm font-bold text-brand-700">{{ number_format($weight * 100, 0) }}%</span>
                            </div>
                            <p class="mt-1 text-sm text-ink-600">{{ $labels[$key][1] ?? '' }}</p>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-ink-100">
                                <div class="h-full rounded-full bg-brand-500" style="width: {{ $weight * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="prose-qm mt-6">
                <p>
                    A resolução pesa mais do que a resposta porque responder sem resolver é fácil.
                    A rapidez pesa menos porque uma resposta rápida que não resolve nada vale pouco —
                    conta, mas não domina.
                </p>
                <p>
                    <strong>Componentes sem dados não penalizam.</strong> Uma empresa sem avaliações
                    de consumidores não é tratada como se tivesse avaliações máximas nem mínimas: o
                    peso dessa componente é redistribuído pelas restantes.
                </p>

                <h2 id="correcao">A correção estatística</h2>
                <p>
                    Para impedir que empresas com muito poucas reclamações apareçam artificialmente
                    no topo ou no fundo, aplicamos uma média bayesiana. Na prática, cada empresa
                    recebe <strong>{{ $priorWeight }} reclamações virtuais</strong> com o valor médio
                    do mercado antes de o seu próprio historial contar:
                </p>
            </div>

            <div class="mt-4 overflow-x-auto rounded-xl bg-ink-900 p-5">
                <pre class="text-sm leading-relaxed text-ink-100"><code>índice = (n × índice_bruto + M × média_do_mercado)
         ─────────────────────────────────────────
                        (n + M)

n = reclamações com oportunidade de resposta na janela
M = {{ $priorWeight }} (constante de suavização)</code></pre>
            </div>

            <div class="prose-qm mt-6">
                <p>
                    O efeito é intuitivo: com poucos dados o índice aproxima-se da média do mercado;
                    à medida que a empresa acumula historial, o seu próprio comportamento passa a
                    dominar. Publicamos sempre o <strong>índice bruto</strong> ao lado do índice
                    final, para que qualquer empresa possa verificar o cálculo.
                </p>

                <h2 id="janela">Janela temporal</h2>
                <p>
                    O índice usa uma <strong>janela móvel de 12 meses</strong>. Mede a empresa que
                    existe hoje, não a de há cinco anos. Uma empresa que mudou de gestão e melhorou
                    vê isso refletido; uma empresa que piorou também. Os snapshots mensais ficam
                    guardados e alimentam os gráficos de evolução e as Marcas do Mês.
                </p>

                <h2 id="prazos">O que conta como "sem resposta"</h2>
                <p>
                    Uma reclamação publicada há dois dias ainda está dentro do prazo razoável. Só
                    entram no cálculo da taxa de resposta as reclamações que já foram respondidas
                    <em>ou</em> que já ultrapassaram <strong>{{ $slaDays }} dias</strong> desde a
                    publicação. Sem esta regra, uma empresa seria penalizada apenas por ter recebido
                    reclamações recentes.
                </p>

                <h2 id="rapidez">Como pontuamos a rapidez</h2>
                <p>
                    Responder em menos de <strong>{{ $speedBest }} horas</strong> vale a pontuação
                    máxima nesta componente. A partir de <strong>{{ round($speedWorst / 24) }} dias</strong>
                    vale zero, e entre os dois a pontuação decresce linearmente. Usar o tempo em bruto
                    faria com que uma única resposta ao fim de seis meses arrasasse a média de uma
                    empresa que normalmente responde em horas.
                </p>

                <h2 id="resolucao">Quem decide que um problema ficou resolvido</h2>
                <p>
                    <strong>Só o consumidor.</strong> A empresa propõe uma solução; a confirmação é
                    de quem reclamou. Se fosse a empresa a fechar os seus próprios processos, a taxa
                    de resolução deixaria de medir resolução e passaria a medir vontade de arquivar.
                </p>

                <h2 id="minimo">Empresas com poucas reclamações</h2>
                <p>
                    Uma empresa só entra no ranking público a partir de
                    <strong>{{ $minimum }} reclamações</strong> publicadas na janela de 12 meses.
                    Abaixo desse limite mostramos "dados insuficientes" na ficha da empresa: qualquer
                    percentagem calculada sobre duas ou três reclamações é ruído, não informação.
                </p>

                <h2 id="excluido">O que fica de fora do índice</h2>
                <ul>
                    <li>Reclamações rejeitadas ou removidas pela moderação.</li>
                    <li>Reclamações de colaboradores e ex-colaboradores — medem uma realidade laboral, não a relação de consumo.</li>
                    <li>Reclamações sobre empresas cuja ficha ainda não foi validada.</li>
                    <li>Reclamações identificadas como fraudulentas, duplicadas ou parte de campanhas coordenadas.</li>
                </ul>

                <h2 id="abuso">Reclamações falsas ou abusivas</h2>
                <p>
                    Todas as reclamações passam por análise humana antes de serem publicadas. Depois
                    da publicação, qualquer pessoa — incluindo a empresa visada — pode denunciar
                    conteúdo. Contas que publiquem reclamações falsas, em nome de terceiros ou em
                    campanha coordenada contra uma empresa são bloqueadas, e as reclamações são
                    removidas do índice.
                </p>
                <p>
                    Contas empresariais que publiquem reclamações contra concorrentes são suspensas.
                </p>

                <h2 id="alteracoes">Alterações à metodologia</h2>
                <p>
                    Se os pesos ou os limiares mudarem, esta página muda com eles — os valores aqui
                    apresentados são lidos da mesma configuração que alimenta o cálculo real, pelo
                    que nunca podem ficar desatualizados relativamente ao que acontece no portal.
                </p>

                <h2 id="limites">O que este índice não é</h2>
                <p>
                    O índice mede como uma empresa trata reclamações publicadas no queixa.me.
                    <strong>Não é</strong> uma avaliação da qualidade dos seus produtos ou serviços,
                    não reflete os clientes satisfeitos que nunca reclamaram, e não constitui
                    recomendação de compra ou de não compra.
                </p>
            </div>
        </article>

        {{-- Índice da página --}}
        <aside class="mt-10 lg:mt-0">
            <nav aria-label="Nesta página" class="lg:sticky lg:top-24">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-500">Nesta página</h2>
                <ul class="space-y-1.5 text-sm">
                    @foreach ([
                        'problema' => 'O problema',
                        'componentes' => 'As quatro componentes',
                        'correcao' => 'Correção estatística',
                        'janela' => 'Janela temporal',
                        'prazos' => 'Prazos de resposta',
                        'rapidez' => 'Pontuação da rapidez',
                        'resolucao' => 'Quem confirma a resolução',
                        'minimo' => 'Empresas com poucos dados',
                        'excluido' => 'O que fica de fora',
                        'abuso' => 'Reclamações abusivas',
                        'limites' => 'O que este índice não é',
                    ] as $anchor => $label)
                        <li>
                            <a href="#{{ $anchor }}" class="block rounded-lg px-2 py-1 text-ink-600 hover:bg-ink-100 hover:text-ink-900">
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </aside>
    </div>
</div>
@endsection
