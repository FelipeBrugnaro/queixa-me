@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')
<div class="container-narrow py-10">

    @include('consumer.wizard._progress', ['step' => $step])

    <header class="mb-8">
        <h1 class="text-3xl sm:text-4xl">Sobre que empresa queres reclamar?</h1>
        <p class="mt-3 text-[0.9375rem] leading-relaxed text-ink-600">
            Começa a escrever e mostramos as empresas que já conhecemos. Se a tua não aparecer,
            adiciona-a — a ficha é validada pela nossa equipa.
        </p>
    </header>

    @guest
        <div class="card mb-6 overflow-hidden">
            <div class="h-1 bg-accent-500"></div>
            <div class="card-body">
                <h2 class="text-lg">Precisas de conta para reclamar</h2>
                <p class="mt-2 text-sm leading-relaxed text-ink-600">
                    É assim que garantimos que cada reclamação tem uma pessoa real por trás — é isso
                    que a torna credível perante a empresa. O teu nome real nunca é publicado.
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn btn-primary">Criar conta</a>
                    <a href="{{ route('login') }}" class="btn btn-secondary">Já tenho conta</a>
                </div>
            </div>
        </div>
    @endguest

    @if ($draft ?? null)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-amber-50 px-5 py-4 ring-1 ring-inset ring-amber-200">
            <p class="text-sm font-semibold text-amber-900">
                Tens uma reclamação por concluir{{ $draft->company ? ' sobre '.$draft->company->name : '' }}.
            </p>
            <a href="{{ route('complaints.wizard.description', $draft->uuid) }}" class="btn btn-sm bg-amber-600 text-white hover:bg-amber-700">
                Continuar
            </a>
        </div>
    @endif

    <form method="POST" action="{{ route('complaints.wizard.company') }}" class="card" data-guard-submit>
        @csrf
        <div class="card-body space-y-7">

            <div>
                <label for="company_name" class="label">
                    Empresa <span class="text-accent-500" aria-hidden="true">*</span>
                </label>

                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <svg class="size-4 text-ink-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3.3 3.3"/>
                        </svg>
                    </div>

                    <input id="company_name" name="company_name" type="text" required
                           value="{{ old('company_name', $prefill) }}"
                           placeholder="Escreve o nome da empresa…"
                           autocomplete="off"
                           role="combobox" aria-expanded="false" aria-autocomplete="list"
                           aria-controls="company_suggestions"
                           data-company-search="company_id"
                           data-endpoint="{{ route('companies.suggest') }}"
                           class="input pl-11 @error('company_name') input-error @enderror">

                    <input type="hidden" id="company_id" name="company_id" value="{{ old('company_id') }}">
                    <input type="hidden" id="company_is_new" value="0">

                    <div id="company_suggestions" hidden role="listbox"
                         class="absolute z-20 mt-2 w-full overflow-hidden rounded-xl bg-surface py-1"
                         style="box-shadow: var(--shadow-float)"></div>
                </div>

                <p class="hint">Indica a entidade responsável pelo problema — a loja, a marca ou o prestador do serviço.</p>
                @error('company_name')<p class="error-text">{{ $message }}</p>@enderror
                @error('company_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <fieldset>
                <legend class="label">Tipo de reclamação <span class="text-accent-500" aria-hidden="true">*</span></legend>
                <div class="mt-1 grid gap-3 sm:grid-cols-2">
                    <label class="choice-card">
                        <input type="radio" name="kind" value="consumer" class="mt-0.5 accent-brand-600" @checked(old('kind', 'consumer') === 'consumer') required>
                        <span>
                            <span class="block text-sm font-bold text-ink-900">Sou consumidor</span>
                            <span class="mt-1 block text-xs leading-relaxed text-ink-500">Compra, encomenda, serviço ou atendimento.</span>
                        </span>
                    </label>

                    <label class="choice-card">
                        <input type="radio" name="kind" value="employee" class="mt-0.5 accent-brand-600" @checked(old('kind') === 'employee')>
                        <span>
                            <span class="block text-sm font-bold text-ink-900">Sou colaborador ou ex-colaborador</span>
                            <span class="mt-1 block text-xs leading-relaxed text-ink-500">Não conta para os índices comerciais da empresa.</span>
                        </span>
                    </label>
                </div>
                @error('kind')<p class="error-text">{{ $message }}</p>@enderror
            </fieldset>

            @auth
                <div class="flex justify-end border-t border-ink-100 pt-6">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Continuar
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 10h11M11 5.5 15.5 10 11 14.5"/>
                        </svg>
                    </button>
                </div>
            @else
                <div class="rounded-xl bg-ink-50 px-4 py-3.5 text-sm text-ink-600">
                    Entra ou cria conta para continuares. Guardamos o que já escreveste.
                </div>
            @endauth
        </div>
    </form>

    <p class="mt-6 text-center text-xs leading-relaxed text-ink-500">
        A tua reclamação será analisada pela nossa equipa antes de ser publicada.
        <a href="{{ route('how-it-works') }}" class="font-semibold text-brand-700 underline decoration-brand-300 underline-offset-2">Ver como funciona</a>
    </p>
</div>
@endsection
