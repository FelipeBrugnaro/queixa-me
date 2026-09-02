@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')
<div class="container-narrow py-8">

    @include('consumer.wizard._progress', ['step' => $step])

    <header class="mb-8">
        <h1 class="text-2xl font-bold sm:text-3xl">Os teus dados</h1>
        <p class="mt-2 text-ink-600">
            Estes dados são transmitidos à empresa para que ela possa identificar o teu processo.
            <strong class="font-semibold text-ink-800">Nunca aparecem publicamente.</strong>
        </p>
    </header>

    {{-- Distinção explícita entre o que é público e o que não é: é a dúvida
         mais comum de quem reclama pela primeira vez. --}}
    <div class="mb-6 grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl bg-emerald-50 p-4 ring-1 ring-inset ring-emerald-200">
            <p class="text-sm font-semibold text-emerald-900">O que fica público</p>
            <ul class="mt-2 ml-4 list-disc space-y-1 text-xs text-emerald-800">
                <li>O teu nome público (ou "anónima", se escolheres)</li>
                <li>O texto da reclamação e o assunto</li>
                <li>A categoria, o distrito e as datas</li>
            </ul>
        </div>
        <div class="rounded-xl bg-ink-100 p-4 ring-1 ring-inset ring-ink-200">
            <p class="text-sm font-semibold text-ink-900">O que nunca fica público</p>
            <ul class="mt-2 ml-4 list-disc space-y-1 text-xs text-ink-600">
                <li>Nome próprio, apelido e morada</li>
                <li>Email e telefone</li>
                <li>Os anexos que juntaste</li>
            </ul>
        </div>
    </div>

    @if ($missingFields)
        <div class="mb-6 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-900 ring-1 ring-inset ring-brand-100">
            Faltam alguns dados no teu perfil. Preenche-os aqui — no fim podes guardá-los para as próximas reclamações.
        </div>
    @endif

    <form method="POST" action="{{ route('complaints.wizard.contact.store', $complaint->uuid) }}" class="card" data-guard-submit>
        @csrf
        <div class="card-body space-y-6">

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="first_name" label="Nome próprio" required :value="$values['first_name']" autocomplete="given-name" />
                <x-field name="last_name" label="Apelido" required :value="$values['last_name']" autocomplete="family-name" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="email" label="Email de contacto" type="email" required :value="$values['email']" autocomplete="email"
                         hint="É por aqui que a empresa te identifica." />
                <x-field name="phone" label="Contacto telefónico" type="tel" :value="$values['phone']" autocomplete="tel" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="address" label="Morada" :value="$values['address']" autocomplete="street-address"
                         hint="Só necessária em casos de entrega, devolução ou reembolso." />
                <x-field name="postal_code" label="Código postal" :value="$values['postal_code']" autocomplete="postal-code" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="locality" label="Localidade" :value="$values['locality']" />
                <x-field name="district" label="Distrito" :value="$values['district']" />
                <x-field name="country" label="País" :value="$values['country']" maxlength="2" />
            </div>

            <fieldset class="space-y-3 rounded-xl bg-ink-50 p-4">
                <legend class="sr-only">Opções de privacidade</legend>

                <label class="flex items-start gap-2.5 text-sm text-ink-700">
                    <input type="checkbox" name="is_identity_public" value="1" class="checkbox"
                           @checked(old('is_identity_public', $complaint->is_identity_public))>
                    <span>
                        <span class="font-medium">Mostrar o meu nome público na reclamação</span>
                        <span class="mt-0.5 block text-xs text-ink-500">
                            Se desativares, a reclamação aparece como anónima. A empresa recebe na mesma os teus dados,
                            porque sem eles não consegue tratar o caso.
                        </span>
                    </span>
                </label>

                <label class="flex items-start gap-2.5 text-sm text-ink-700">
                    <input type="checkbox" name="save_to_profile" value="1" class="checkbox" @checked(old('save_to_profile', true))>
                    <span>
                        <span class="font-medium">Guardar estas informações no meu perfil</span>
                        <span class="mt-0.5 block text-xs text-ink-500">
                            Para não teres de as escrever outra vez em futuras reclamações.
                        </span>
                    </span>
                </label>
            </fieldset>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('complaints.wizard.details', $complaint->uuid) }}" class="btn btn-ghost">Voltar</a>
                <button type="submit" class="btn btn-primary">Continuar</button>
            </div>
        </div>
    </form>
</div>
@endsection
