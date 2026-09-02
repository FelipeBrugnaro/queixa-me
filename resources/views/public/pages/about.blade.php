@extends('layouts.app')

@section('content')
<div class="container-narrow py-8">

    <header>
        <h1 class="text-3xl font-bold sm:text-4xl">Sobre o queixa.me</h1>
        <p class="mt-4 text-lg leading-relaxed text-ink-600">
            Existimos porque reclamar sozinho quase nunca resulta, e porque as empresas que
            querem resolver raramente têm onde o mostrar.
        </p>
    </header>

    <div class="prose-qm mt-10">
        <h2>Três pessoas, o mesmo problema</h2>
        <p>O queixa.me foi construído à volta de três frases:</p>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['Consumidor', 'Tive um problema e quero ser ouvido.'],
            ['Empresa', 'Quero saber o que aconteceu e ter oportunidade de responder.'],
            ['Comunidade', 'Quero saber como outras pessoas foram tratadas antes de comprar.'],
        ] as [$who, $quote])
            <div class="card">
                <div class="card-body">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">{{ $who }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-ink-700">&ldquo;{{ $quote }}&rdquo;</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="prose-qm mt-10">
        <p>
            As três importam por igual. Um portal que só amplifique reclamações transforma-se num
            mural de raiva; um portal que proteja empresas deixa de ser útil a quem procura
            informação. O equilíbrio está em medir <strong>reclamação, resposta e resolução</strong>
            com o mesmo peso — e em premiar quem resolve, não em castigar quem recebe reclamações.
        </p>

        <h2>Aquilo que decidimos não fazer</h2>
        <ul>
            <li><strong>Não ordenamos empresas por número de reclamações.</strong> Esse número mede dimensão, não qualidade.</li>
            <li><strong>Não deixamos a empresa fechar os seus próprios processos.</strong> A resolução é confirmada por quem reclamou.</li>
            <li><strong>Não publicamos nada sem análise humana prévia.</strong> Nem a reclamação mais bem-intencionada deve expor dados pessoais.</li>
            <li><strong>Não removemos reclamações por serem incómodas.</strong> Removemos o que é ilícito, ofensivo ou falso.</li>
            <li><strong>Não vendemos posições no ranking.</strong> As distinções resultam de critérios públicos aplicados automaticamente.</li>
        </ul>

        <h2>O que não somos</h2>
        <p>
            O queixa.me é uma plataforma privada e independente. Não é uma entidade oficial de
            resolução de litígios e não substitui o Livro de Reclamações, as entidades reguladoras,
            os centros de arbitragem, os organismos de resolução alternativa de litígios nem os
            tribunais. Publicar aqui não interrompe prazos legais.
        </p>

        <h2>Privacidade em primeiro lugar</h2>
        <p>
            O único nome visível numa reclamação é o nome público, que a pessoa escolhe. O nome
            civil, o email, o telefone e a morada nunca aparecem publicamente — são transmitidos
            apenas à empresa visada, com consentimento explícito e registado. Os anexos são
            privados por omissão e servidos apenas a quem tem autorização. Sabe mais na
            <a href="{{ route('legal.privacy') }}">Política de Privacidade</a>.
        </p>

        <h2>Transparência dos números</h2>
        <p>
            Todo o método de cálculo dos índices está publicado, incluindo os pesos de cada
            componente e as correções estatísticas aplicadas. Se não conseguires reproduzir um
            número que publicamos, é um erro nosso —
            <a href="{{ route('contact') }}">diz-nos</a>. Vê a
            <a href="{{ route('methodology') }}">metodologia completa</a>.
        </p>
    </div>

    <div class="mt-12 rounded-2xl bg-ink-900 px-6 py-10 text-center">
        <h2 class="text-xl font-semibold text-white">Tens uma questão sobre o portal?</h2>
        <a href="{{ route('contact') }}" class="btn btn-primary mt-5">Falar connosco</a>
    </div>
</div>
@endsection
