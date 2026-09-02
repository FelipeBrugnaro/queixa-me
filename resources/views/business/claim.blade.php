@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')
<div class="container-narrow py-10">

    <header class="text-center">
        <h1 class="text-3xl font-bold">Associar a minha empresa</h1>
        <p class="mt-3 text-ink-600">
            Validamos a tua ligação à marca antes de te dar acesso às reclamações — elas contêm
            dados pessoais de consumidores.
        </p>
    </header>

    <form method="POST" action="{{ route('business.claim.store') }}" class="card mt-8" data-guard-submit>
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

                <p class="hint">Se a ficha já existir, seleciona-a da lista para não criar um duplicado.</p>
                @error('company_name')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <x-field name="website" label="Website oficial" type="url" placeholder="https://…"
                     hint="Usamos o domínio para confirmar automaticamente o teu email profissional." />

            <x-field name="work_email" label="O teu email profissional" type="email" required
                     hint="Um email no domínio da empresa acelera muito a validação." />

            <x-field name="vat_number" label="NIF da empresa"
                     hint="Ajuda a identificar a entidade certa. Por si só não valida a reivindicação — o NIF é informação pública." />

            <x-field name="evidence" label="Como podemos confirmar a tua ligação à empresa?" type="textarea" rows="4"
                     placeholder="ex.: Sou responsável pelo apoio ao cliente. Consta o meu nome na página de contactos do site."
                     hint="Quanto mais concreto, mais rápida é a validação." />

            <div class="rounded-xl bg-ink-100 px-4 py-3 text-xs leading-relaxed text-ink-600">
                <strong class="font-semibold text-ink-800">Porque é que isto é revisto por uma pessoa.</strong>
                Uma reivindicação aceite dá acesso a dados pessoais de consumidores e ao direito de
                responder em nome da marca. Nenhum sinal automático é suficiente para isso sozinho.
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">Submeter pedido</button>
            </div>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        Não encontras a tua empresa? Escreve o nome e criamos a ficha durante a validação.
    </p>
</div>
@endsection
