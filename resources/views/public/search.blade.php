@extends('layouts.app')

@section('content')
<div class="container-page py-8">

    <form method="GET" role="search" class="mx-auto max-w-2xl">
        <label for="q" class="sr-only">Pesquisar</label>
        <div class="flex items-center gap-2 rounded-2xl bg-white p-2 ring-1 ring-ink-200 shadow-sm focus-within:ring-2 focus-within:ring-brand-600">
            <svg class="ml-2 size-5 shrink-0 text-ink-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.4 9.83l3.63 3.64a.75.75 0 1 0 1.06-1.06l-3.63-3.64A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd"/>
            </svg>
            <input id="q" type="search" name="q" value="{{ $term }}" autofocus
                   placeholder="Empresa, marca, produto ou palavra-chave"
                   class="min-w-0 flex-1 border-0 bg-transparent py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:ring-0">
            <button type="submit" class="btn btn-primary shrink-0">Pesquisar</button>
        </div>
    </form>

    @if ($term === '')
        <p class="mt-10 text-center text-sm text-ink-500">Escreve pelo menos duas letras para pesquisar.</p>
    @elseif ($total === 0)
        <div class="mx-auto mt-10 max-w-xl">
            <x-empty-state
                title="Sem resultados para &ldquo;{{ $term }}&rdquo;"
                description="Talvez a empresa ainda não esteja no portal. Podes indicá-la ao criar a tua reclamação.">
                <a href="{{ route('complaints.create', ['empresa' => $term]) }}" class="btn btn-primary">Reclamar sobre "{{ $term }}"</a>
            </x-empty-state>
        </div>
    @else
        <div class="mt-10 space-y-12">
            @if ($companies->isNotEmpty())
                <section aria-labelledby="r-empresas">
                    <h2 id="r-empresas" class="mb-4 text-lg font-semibold">Empresas</h2>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($companies as $company)
                            <x-company-card :company="$company" />
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($complaints->isNotEmpty())
                <section aria-labelledby="r-reclamacoes">
                    <h2 id="r-reclamacoes" class="mb-4 text-lg font-semibold">Reclamações</h2>
                    <div class="space-y-4">
                        @foreach ($complaints as $complaint)
                            <x-complaint-card :complaint="$complaint" />
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($posts->isNotEmpty())
                <section aria-labelledby="r-artigos">
                    <h2 id="r-artigos" class="mb-4 text-lg font-semibold">Artigos</h2>
                    <ul class="grid gap-4 md:grid-cols-2">
                        @foreach ($posts as $post)
                            <li class="card">
                                <a href="{{ $post->url() }}" class="block p-5 hover:bg-ink-50">
                                    <p class="font-medium text-ink-900">{{ $post->title }}</p>
                                    <p class="mt-1 line-clamp-2 text-sm text-ink-600">{{ $post->excerpt }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    @endif
</div>
@endsection
