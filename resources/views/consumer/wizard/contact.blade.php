@extends('layouts.app', ['hideBreadcrumbs' => true])

@php use App\Domain\Shared\Support\Countries; @endphp

@section('content')
<div class="container-narrow py-10">

    @include('consumer.wizard._progress', ['step' => $step])

    <header class="mb-8">
        <h1 class="text-3xl sm:text-4xl">Os teus dados</h1>
        <p class="mt-3 text-[0.9375rem] leading-relaxed text-ink-600">
            Servem para a empresa identificar o teu processo.
            <strong class="font-bold text-ink-900">Nunca aparecem publicamente.</strong>
        </p>
    </header>

    {{-- O que é público e o que não é: é a dúvida número um de quem reclama
         pela primeira vez, por isso é respondida antes de se pedir seja o
         que for. --}}
    <div class="mb-8 grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl bg-brand-50 p-5 ring-1 ring-inset ring-brand-200">
            <div class="flex items-center gap-2">
                <span class="flex size-6 items-center justify-center rounded-full bg-brand-600 text-white" aria-hidden="true">
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2.5 10s2.8-5 7.5-5 7.5 5 7.5 5-2.8 5-7.5 5-7.5-5-7.5-5Z"/><circle cx="10" cy="10" r="2"/>
                    </svg>
                </span>
                <p class="text-sm font-extrabold text-brand-900">Fica público</p>
            </div>
            <ul class="mt-3 space-y-1.5 text-xs leading-relaxed text-brand-800">
                <li>Como assinares a reclamação (abaixo)</li>
                <li>O texto da reclamação e o assunto</li>
                <li>A categoria, o distrito e as datas</li>
            </ul>
        </div>

        <div class="rounded-2xl bg-ink-100 p-5 ring-1 ring-inset ring-ink-200">
            <div class="flex items-center gap-2">
                <span class="flex size-6 items-center justify-center rounded-full bg-ink-600 text-white" aria-hidden="true">
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="8.5" width="12" height="8" rx="2"/><path d="M7 8.5V6a3 3 0 0 1 6 0v2.5"/>
                    </svg>
                </span>
                <p class="text-sm font-extrabold text-ink-900">Nunca fica público</p>
            </div>
            <ul class="mt-3 space-y-1.5 text-xs leading-relaxed text-ink-600">
                <li>Nome próprio, apelido e morada</li>
                <li>Email e telefone</li>
                <li>Os anexos que juntaste</li>
            </ul>
        </div>
    </div>

    <form method="POST" action="{{ route('complaints.wizard.contact.store', $complaint->uuid) }}" class="card" data-guard-submit>
        @csrf
        <div class="card-body space-y-7">

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="first_name" label="Nome próprio" required :value="$values['first_name']" autocomplete="given-name" />
                <x-field name="last_name" label="Apelido" required :value="$values['last_name']" autocomplete="family-name" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="email" label="Email de contacto" type="email" required :value="$values['email']" autocomplete="email"
                         hint="É por aqui que a empresa te identifica." />
                <x-field name="phone" label="Contacto telefónico" type="tel" :value="$values['phone']" autocomplete="tel" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="country" label="País" type="select" required
                         :value="$values['country'] ?: config('countries.default')"
                         :options="Countries::options()" />
                <x-field name="district" label="Distrito" type="select" :value="$values['district']"
                         :options="collect($districts)->mapWithKeys(fn ($d) => [$d => $d])->all()"
                         placeholder="Selecionar distrito" />
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <x-field name="locality" label="Localidade" :value="$values['locality']" class="sm:col-span-1" />
                <x-field name="address" label="Morada" :value="$values['address']" autocomplete="street-address" class="sm:col-span-1"
                         hint="Só necessária em entregas, devoluções ou reembolsos." />
                <x-field name="postal_code" label="Código postal" :value="$values['postal_code']" autocomplete="postal-code" class="sm:col-span-1" />
            </div>

            {{-- Assinatura da reclamação --}}
            <fieldset class="border-t border-ink-100 pt-7">
                <legend class="label">Como queres assinar a reclamação? <span class="text-accent-500" aria-hidden="true">*</span></legend>

                <div class="mt-1 grid gap-3 sm:grid-cols-2">
                    <label class="choice-card">
                        <input type="radio" name="signature" value="public" class="mt-0.5 accent-brand-600"
                               @checked(old('signature', $complaint->is_identity_public ? 'public' : 'anonymous') === 'public') required>
                        <span>
                            <span class="block text-sm font-bold text-ink-900">
                                Com o meu nome público
                            </span>
                            <span class="mt-1 block font-mono text-xs text-brand-700">
                                {{ auth()->user()->publicDisplayName() }}
                            </span>
                            <span class="mt-1.5 block text-xs leading-relaxed text-ink-500">
                                O nome público é o pseudónimo que escolheste, não o teu nome real.
                            </span>
                        </span>
                    </label>

                    <label class="choice-card">
                        <input type="radio" name="signature" value="anonymous" class="mt-0.5 accent-brand-600"
                               @checked(old('signature', $complaint->is_identity_public ? 'public' : 'anonymous') === 'anonymous')>
                        <span>
                            <span class="block text-sm font-bold text-ink-900">De forma anónima</span>
                            <span class="mt-1 block font-mono text-xs text-ink-500">Reclamação anónima</span>
                            <span class="mt-1.5 block text-xs leading-relaxed text-ink-500">
                                Ninguém vê quem reclamou. A empresa continua a receber os teus dados
                                para poder resolver.
                            </span>
                        </span>
                    </label>
                </div>

                @error('signature')<p class="error-text">{{ $message }}</p>@enderror
            </fieldset>

            <div class="flex items-center justify-between gap-3 border-t border-ink-100 pt-6">
                <a href="{{ route('complaints.wizard.details', $complaint->uuid) }}" class="btn btn-ghost">Voltar</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    Continuar
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 10h11M11 5.5 15.5 10 11 14.5"/>
                    </svg>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
