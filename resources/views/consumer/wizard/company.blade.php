@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')
<div class="container-narrow py-8">

    @include('consumer.wizard._progress', ['step' => $step])

    <header class="mb-8">
        <h1 class="text-2xl font-bold sm:text-3xl">Sobre que empresa queres reclamar?</h1>
        <p class="mt-2 text-ink-600">
            Começa a escrever e mostramos as empresas que já conhecemos. Se a tua não aparecer,
            podes adicioná-la — a ficha é validada pela nossa equipa.
        </p>
    </header>

    @guest
        <div class="card mb-6">
            <div class="card-body">
                <h2 class="font-semibold">Precisas de conta para reclamar</h2>
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
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
            <span>Tens uma reclamação por concluir{{ $draft->company ? ' sobre '.$draft->company->name : '' }}.</span>
            <a href="{{ route('complaints.wizard.description', $draft->uuid) }}" class="btn btn-sm bg-amber-600 text-white hover:bg-amber-700">
                Continuar
            </a>
        </div>
    @endif

    <form method="POST" action="{{ route('complaints.wizard.company') }}" class="card" data-guard-submit>
        @csrf
        <div class="card-body space-y-6">

            <div>
                <label for="company_name" class="label">
                    Empresa <span class="text-rose-600" aria-hidden="true">*</span>
                </label>

                <div class="relative">
                    <input id="company_name" name="company_name" type="text" required
                           value="{{ old('company_name', $prefill) }}"
                           placeholder="Escreve o nome da empresa…"
                           autocomplete="off"
                           role="combobox" aria-expanded="false" aria-autocomplete="list"
                           aria-controls="company_suggestions"
                           data-company-search="company_id"
                           data-endpoint="{{ route('companies.suggest') }}"
                           class="input @error('company_name') input-error @enderror">

                    <input type="hidden" id="company_id" name="company_id" value="{{ old('company_id') }}">
                    <input type="hidden" id="company_is_new" value="0">

                    <div id="company_suggestions" hidden role="listbox"
                         class="absolute z-20 mt-1 w-full overflow-hidden rounded-xl bg-white py-1 ring-1 ring-ink-200 shadow-lg"></div>
                </div>

                <p class="hint">Indica a entidade responsável pelo problema — a loja, a marca ou o prestador do serviço.</p>
                @error('company_name')<p class="error-text">{{ $message }}</p>@enderror
                @error('company_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <x-field name="company_website" label="Site da empresa" type="url"
                     placeholder="https://…"
                     hint="Ajuda-nos a identificar a empresa certa e a evitar fichas duplicadas." />

            <fieldset>
                <legend class="label">Tipo de reclamação <span class="text-rose-600" aria-hidden="true">*</span></legend>
                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl p-4 ring-1 ring-ink-200 transition hover:bg-ink-50 has-checked:bg-brand-50 has-checked:ring-brand-300">
                        <input type="radio" name="kind" value="consumer" class="mt-0.5" @checked(old('kind', 'consumer') === 'consumer') required>
                        <span>
                            <span class="block text-sm font-medium text-ink-900">Sou consumidor</span>
                            <span class="mt-0.5 block text-xs text-ink-500">Compra, encomenda, serviço ou atendimento.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl p-4 ring-1 ring-ink-200 transition hover:bg-ink-50 has-checked:bg-brand-50 has-checked:ring-brand-300">
                        <input type="radio" name="kind" value="employee" class="mt-0.5" @checked(old('kind') === 'employee')>
                        <span>
                            <span class="block text-sm font-medium text-ink-900">Sou colaborador ou ex-colaborador</span>
                            <span class="mt-0.5 block text-xs text-ink-500">Não conta para os índices comerciais da empresa.</span>
                        </span>
                    </label>
                </div>
                @error('kind')<p class="error-text">{{ $message }}</p>@enderror
            </fieldset>

            @auth
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Continuar</button>
                </div>
            @else
                <div class="rounded-xl bg-ink-100 px-4 py-3 text-sm text-ink-600">
                    Entra ou cria conta para continuares. Guardamos o que já escreveste.
                </div>
            @endauth
        </div>
    </form>

    <p class="mt-6 text-center text-xs leading-relaxed text-ink-500">
        Ao continuares, a tua reclamação será analisada pela nossa equipa antes de ser publicada.
        <a href="{{ route('how-it-works') }}" class="underline underline-offset-2 hover:text-ink-700">Ver como funciona</a>
    </p>
</div>
@endsection
