@extends('layouts.app')

@section('content')
<div class="container-narrow py-8">

    <header>
        <h1 class="text-3xl font-bold sm:text-4xl">{{ $document?->title ?? $fallbackTitle }}</h1>
        @if ($document)
            <p class="mt-3 text-sm text-ink-500">
                Versão {{ $document->version }}
                @if ($document->effective_from)
                    · em vigor desde {{ $document->effective_from->translatedFormat('j \d\e F \d\e Y') }}
                @endif
            </p>
        @endif
    </header>

    @if ($document)
        <div class="prose-qm mt-8">{!! $document->body !!}</div>

        <p class="mt-12 rounded-xl bg-ink-100 px-4 py-3 text-xs leading-relaxed text-ink-600">
            Guardamos o registo de qual versão deste documento cada pessoa aceitou, com data e hora.
            Quando o documento muda de forma material, pedimos nova aceitação.
        </p>
    @else
        <div class="mt-8">
            <x-empty-state
                title="Documento ainda não publicado"
                description="Este documento está em preparação. Contacta-nos se precisares de esclarecimentos.">
                <a href="{{ route('contact') }}" class="btn btn-secondary">Contactar</a>
            </x-empty-state>
        </div>
    @endif
</div>
@endsection
