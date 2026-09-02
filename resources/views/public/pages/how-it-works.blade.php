@extends('layouts.app')

@section('content')
<div class="container-page py-8">

    <header class="mx-auto max-w-3xl text-center">
        <h1 class="text-3xl font-bold sm:text-4xl">Como funciona o queixa.me</h1>
        <p class="mt-4 text-lg leading-relaxed text-ink-600">
            Do momento em que escreves a reclamação até à confirmação de que o problema ficou
            resolvido. Sem custos, sem intermediários e sem burocracia.
        </p>
    </header>

    {{-- Percurso do consumidor --}}
    <section class="mt-14" aria-labelledby="consumidores">
        <h2 id="consumidores" class="text-2xl font-semibold">Para quem reclama</h2>

        <ol class="mt-8 space-y-6">
            @foreach ([
                ['Escreves a reclamação', 'Identificas a empresa, descreves o que aconteceu e indicas o que pretendes que seja feito. Podes juntar faturas, comprovativos e fotografias. O rascunho fica gravado — podes fechar o browser e continuar depois.'],
                ['Confirmas os teus dados e o consentimento', 'Pré-preenchemos o que já sabemos do teu perfil. No fim, autorizas de forma explícita a transmissão dos teus dados de contacto à empresa visada. Sem essa autorização a reclamação não segue, porque a empresa não conseguiria identificar o teu caso.'],
                ['A nossa equipa analisa', 'Verificamos que o texto não expõe dados pessoais teus ou de terceiros, que não contém linguagem ofensiva e que tem elementos suficientes para a empresa responder. Se faltar algo, devolvemos com o motivo concreto. Normalmente demora menos de 48 horas.'],
                ['A reclamação é publicada e a empresa notificada', 'A partir daqui a reclamação tem página própria, aparece nos motores de busca e conta para os indicadores públicos da empresa.'],
                ['A empresa responde', 'Publicamente, no fio da reclamação, e — quando for preciso tratar dados que não devem ser públicos — também por mensagem privada.'],
                ['Confirmas o desfecho', 'Só tu podes dar o problema como resolvido, e avalias a experiência de 1 a 5. É essa confirmação que conta para a taxa de resolução da empresa.'],
            ] as $index => [$title, $description])
                <li class="card">
                    <div class="card-body flex gap-5">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-sm font-bold text-white">
                            {{ $index + 1 }}
                        </span>
                        <div class="min-w-0">
                            <h3 class="font-semibold">{{ $title }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-ink-600">{{ $description }}</p>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Prazos --}}
    <section class="mt-14" aria-labelledby="prazos">
        <h2 id="prazos" class="text-2xl font-semibold">Prazos que aplicamos</h2>
        <p class="mt-2 max-w-3xl text-ink-600">
            Estes prazos são regras do portal, não prazos legais. Servem para que os indicadores
            sejam comparáveis entre empresas.
        </p>

        <dl class="mt-6 grid gap-4 sm:grid-cols-3">
            @foreach ([
                [$slaDays.' dias', 'Prazo de resposta', 'Passado este prazo sem resposta, a reclamação passa a contar como não respondida nos indicadores da empresa.'],
                [$confirmDays.' dias', 'Confirmação da solução', 'Depois de a empresa propor uma solução, tens este prazo para confirmar ou recusar.'],
                [$autoCloseDays.' dias', 'Encerramento automático', 'Sem qualquer atividade durante este período, a reclamação é encerrada. Podes reabri-la.'],
            ] as [$value, $label, $description])
                <div class="card">
                    <div class="card-body">
                        <dt class="text-2xl font-bold text-brand-700">{{ $value }}</dt>
                        <dd>
                            <p class="mt-1 font-semibold">{{ $label }}</p>
                            <p class="mt-1 text-sm text-ink-600">{{ $description }}</p>
                        </dd>
                    </div>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Empresas --}}
    <section id="empresas" class="mt-14 scroll-mt-24" aria-labelledby="empresas-titulo">
        <h2 id="empresas-titulo" class="text-2xl font-semibold">Para as empresas</h2>
        <p class="mt-2 max-w-3xl text-ink-600">
            Reclamar em público só funciona se houver direito de resposta em público. É por isso que
            reivindicar a ficha da empresa é gratuito e sem condições.
        </p>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            @foreach ([
                ['Reivindicar a ficha', 'Criar conta e pedir a associação à empresa. Validamos a ligação à marca — um email no domínio oficial acelera o processo.'],
                ['Receber e responder', 'As reclamações aparecem no painel logo que são publicadas, ordenadas por urgência: primeiro as que ainda não têm resposta, e dentro dessas as mais antigas.'],
                ['Tratar o que não deve ser público', 'O canal de mensagem privada existe para números de encomenda, moradas e dados de reembolso. O consumidor pode fechá-lo a qualquer momento.'],
                ['Acompanhar os indicadores', 'O painel mostra a composição exata do índice da empresa, o que está a puxar o valor para baixo e a evolução mês a mês.'],
            ] as [$title, $description])
                <div class="card">
                    <div class="card-body">
                        <h3 class="font-semibold">{{ $title }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink-600">{{ $description }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 rounded-xl bg-brand-50 px-5 py-4 text-sm leading-relaxed text-brand-900 ring-1 ring-inset ring-brand-100">
            <strong class="font-semibold">Uma nota sobre remoções.</strong>
            Não removemos reclamações por serem desfavoráveis. Removemos conteúdo que exponha dados
            pessoais, que seja ofensivo ou comprovadamente falso. O direito de resposta existe
            precisamente para o resto.
        </div>
    </section>

    {{-- O que não somos --}}
    <section class="mt-14" aria-labelledby="limites">
        <h2 id="limites" class="text-2xl font-semibold">O que o queixa.me não é</h2>
        <div class="mt-6 card">
            <div class="card-body prose-qm">
                <p>
                    O queixa.me é um portal privado e independente. <strong>Não é uma entidade oficial
                    de resolução de conflitos</strong> e não substitui:
                </p>
                <ul>
                    <li>o Livro de Reclamações (físico ou eletrónico);</li>
                    <li>as entidades reguladoras sectoriais;</li>
                    <li>os centros de arbitragem e os organismos de resolução alternativa de litígios;</li>
                    <li>as associações de defesa do consumidor;</li>
                    <li>os tribunais.</li>
                </ul>
                <p>
                    Publicar aqui não interrompe nem suspende prazos legais. Se o teu caso exigir uma
                    decisão vinculativa, usa os canais oficiais — e usa o queixa.me em paralelo, para
                    que a tua experiência fique registada e visível.
                </p>
            </div>
        </div>
    </section>

    <div class="mt-14 rounded-3xl bg-ink-900 px-6 py-12 text-center">
        <h2 class="text-2xl font-semibold text-white">Pronto para começar?</h2>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('complaints.create') }}" class="btn btn-primary btn-lg">Fazer uma reclamação</a>
            <a href="{{ route('register.business') }}" class="btn btn-lg bg-white/10 text-white hover:bg-white/20">Sou uma empresa</a>
        </div>
    </div>
</div>
@endsection
