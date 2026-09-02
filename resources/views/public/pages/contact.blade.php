@extends('layouts.app')

@section('content')
<div class="container-narrow py-8">

    <header>
        <h1 class="text-3xl font-bold sm:text-4xl">Contactos</h1>
        <p class="mt-3 text-ink-600">
            Este canal é para assuntos relacionados com o portal.
        </p>
    </header>

    <div class="mt-6 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
        <strong class="font-semibold">Queres reclamar de uma empresa?</strong>
        Este formulário não serve para isso — a tua mensagem não chegaria à empresa.
        <a href="{{ route('complaints.create') }}" class="font-semibold underline underline-offset-2">Usa antes o formulário de reclamação</a>.
    </div>

    <form method="POST" action="{{ route('contact.submit') }}" class="card mt-8" data-guard-submit>
        @csrf
        <div class="card-body space-y-5">
            <div class="hidden" aria-hidden="true">
                <label for="website">Não preencher</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="name" label="O teu nome" required autocomplete="name" />
                <x-field name="email" label="Email para resposta" type="email" required autocomplete="email" />
            </div>

            <x-field name="subject" label="Assunto" required :value="request()->query('assunto')" />

            <div>
                <x-field name="message" label="Mensagem" type="textarea" required rows="8"
                         id="contact_message"
                         placeholder="Descreve a tua questão com o máximo de detalhe possível." />
                <p data-counter-for="contact_message" data-counter-min="20" data-counter-max="4000" class="hint text-right"></p>
            </div>

            <button type="submit" class="btn btn-primary">Enviar mensagem</button>
        </div>
    </form>

    <div class="mt-10 grid gap-4 sm:grid-cols-2">
        <div class="card">
            <div class="card-body">
                <h2 class="font-semibold">Assuntos gerais</h2>
                <p class="mt-1.5 text-sm text-ink-600">
                    <a href="mailto:{{ config('queixame.brand.contact_email') }}" class="text-brand-700 hover:text-brand-800">
                        {{ config('queixame.brand.contact_email') }}
                    </a>
                </p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h2 class="font-semibold">Proteção de dados</h2>
                <p class="mt-1.5 text-sm text-ink-600">
                    <a href="mailto:{{ config('queixame.brand.dpo_email') }}" class="text-brand-700 hover:text-brand-800">
                        {{ config('queixame.brand.dpo_email') }}
                    </a>
                </p>
                <p class="mt-1 text-xs text-ink-500">
                    Para exercer direitos sobre os teus dados, usa antes a
                    <a href="{{ route('consumer.privacy') }}" class="underline underline-offset-2">área de privacidade</a> da tua conta.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
