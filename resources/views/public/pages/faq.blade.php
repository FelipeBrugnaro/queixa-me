@extends('layouts.app')

@section('content')
<div class="container-page py-8">
    <div class="mx-auto max-w-3xl">

        <header class="text-center">
            <h1 class="text-3xl font-bold sm:text-4xl">Perguntas frequentes</h1>
            <p class="mt-4 text-ink-600">
                Se não encontrares aqui a resposta, <a href="{{ route('contact') }}" class="font-semibold text-brand-700 hover:text-brand-800">fala connosco</a>.
            </p>
        </header>

        <nav aria-label="Público-alvo" class="mt-8 flex justify-center gap-2">
            <a href="{{ route('faq') }}" class="badge {{ $audience === 'consumer' ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-ink-700 ring-ink-200 hover:bg-ink-50' }}">
                Consumidores
            </a>
            <a href="{{ route('faq', ['publico' => 'empresas']) }}" class="badge {{ $audience === 'business' ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-ink-700 ring-ink-200 hover:bg-ink-50' }}">
                Empresas
            </a>
        </nav>

        <div class="mt-10 space-y-10">
            @foreach ($categories as $category)
                <section aria-labelledby="faq-{{ $category->slug }}">
                    <h2 id="faq-{{ $category->slug }}" class="mb-4 text-lg font-semibold">{{ $category->name }}</h2>

                    <div class="space-y-3">
                        @foreach ($category->items as $item)
                            <details class="card group" @if($loop->parent->first && $loop->first) open @endif>
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-medium text-ink-900">
                                    <span>{{ $item->question }}</span>
                                    <svg class="size-5 shrink-0 text-ink-400 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                                    </svg>
                                </summary>
                                <div class="border-t border-ink-100 px-5 py-4 text-sm leading-relaxed text-ink-600">
                                    {!! nl2br(e($item->answer)) !!}
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
@endsection
