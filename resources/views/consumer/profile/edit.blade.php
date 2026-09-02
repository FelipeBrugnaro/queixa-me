@extends('layouts.panel')

@php use App\Domain\Shared\Support\Countries; @endphp

@section('panel-heading')
    <h1 class="text-2xl">Perfil</h1>
    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
        <div class="index-track max-w-48">
            <div class="index-fill bg-brand-500" style="width: {{ $user->profileCompletion() }}%"></div>
        </div>
        <p class="text-xs font-semibold text-ink-500">
            Perfil {{ $user->profileCompletion() }}% completo
        </p>
    </div>
@endsection

@section('panel')
<div class="space-y-6">

    {{--
        Um cartão para a identidade, não dois.

        A fotografia é parte de quem a pessoa é: separá-la dos restantes
        dados obrigava a saltar entre cartões para completar uma ideia só.
    --}}
    <form method="POST" action="{{ route('consumer.profile.update') }}" class="card overflow-hidden" data-guard-submit>
        @csrf
        @method('PATCH')

        <div class="card-body">
            <h2 class="text-lg">Identidade</h2>

            <div class="mt-6 flex flex-col gap-6 sm:flex-row">

                {{-- Fotografia --}}
                <div class="shrink-0 text-center sm:w-36">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt=""
                             class="mx-auto size-28 rounded-2xl object-cover ring-1 ring-ink-200">
                    @else
                        <span class="mx-auto flex size-28 items-center justify-center rounded-2xl text-3xl font-extrabold text-white"
                              style="background: linear-gradient(140deg, var(--color-brand-400), var(--color-brand-700))"
                              aria-hidden="true">
                            {{ $user->initials() }}
                        </span>
                    @endif

                    <label for="avatar-input"
                           class="mt-3 inline-flex cursor-pointer items-center gap-1.5 text-xs font-bold text-brand-700 transition hover:text-brand-800">
                        <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10 14V6M6.5 9.5 10 6l3.5 3.5M4 15.5h12"/>
                        </svg>
                        {{ $user->avatar_path ? 'Alterar foto' : 'Carregar foto' }}
                    </label>

                    @if ($user->avatar_path)
                        <button type="submit" form="remove-avatar"
                                class="mt-1.5 block w-full text-xs font-semibold text-ink-400 transition hover:text-rose-600">
                            Remover
                        </button>
                    @endif

                    @error('avatar')<p class="error-text justify-center">{{ $message }}</p>@enderror
                </div>

                {{-- Dados --}}
                <div class="min-w-0 flex-1 space-y-5">
                    <x-field name="public_name" label="Nome público" required :value="$user->public_name"
                             hint="É o único nome visível nas reclamações publicadas." />

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="first_name" label="Nome próprio" required :value="$user->first_name" />
                        <x-field name="last_name" label="Apelido" required :value="$user->last_name" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="birthdate" label="Data de nascimento" type="date"
                                 :value="$user->birthdate?->toDateString()" max="{{ now()->toDateString() }}" />
                        <x-field name="gender" label="Género" type="select" :value="$user->gender?->value" :options="$genders" />
                    </div>
                </div>
            </div>

            {{-- Localização --}}
            <div class="mt-7 border-t border-ink-100 pt-7">
                <h3 class="text-sm font-bold text-ink-800">Onde vives</h3>
                <p class="mt-1 text-xs text-ink-500">
                    Usado para pré-preencher as tuas reclamações. No portal só aparece o distrito.
                </p>

                <div class="mt-4 grid gap-5 sm:grid-cols-3">
                    <x-field name="country" label="País" type="select"
                             :value="$user->country ?: config('countries.default')"
                             :options="Countries::options()"
                             placeholder="Selecionar país" />
                    <x-field name="district" label="Distrito" type="select" :value="$user->district"
                             :options="collect($districts)->mapWithKeys(fn ($d) => [$d => $d])->all()"
                             placeholder="Selecionar distrito" />
                    <x-field name="locality" label="Localidade" :value="$user->locality" />
                </div>
            </div>

            <div class="mt-7 flex justify-end border-t border-ink-100 pt-6">
                <button type="submit" class="btn btn-primary">Guardar alterações</button>
            </div>
        </div>
    </form>

    {{-- Formulários da fotografia, fora do formulário principal.
         O campo fica visualmente escondido mas não display:none — um input
         com display:none não abre o seletor de ficheiros ao clicar no rótulo. --}}
    <form id="avatar-form" method="POST" action="{{ route('consumer.profile.avatar') }}"
          enctype="multipart/form-data">
        @csrf
        <input id="avatar-input" type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
               class="sr-only" onchange="this.form.submit()">
    </form>

    @if ($user->avatar_path)
        <form id="remove-avatar" method="POST" action="{{ route('consumer.profile.avatar.destroy') }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif

    {{--
        Contactos e segurança num só cartão com secções.

        Antes eram quatro cartões seguidos — email, telefone, palavra-passe,
        contas ligadas — todos com o mesmo peso visual, o que fazia a página
        parecer uma pilha. Agrupados, lê-se como um único assunto: como se
        entra na conta e como se fala contigo.
    --}}
    <div class="card">
        <div class="card-body">
            <h2 class="text-lg">Contactos e segurança</h2>

            {{-- Email --}}
            <section class="mt-6" aria-labelledby="sec-email">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 id="sec-email" class="text-sm font-bold text-ink-800">Endereço de email</h3>
                        <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-ink-600">
                            {{ $user->email }}
                            @if ($user->hasVerifiedEmail())
                                <span class="badge bg-brand-50 text-brand-700 ring-brand-200">Confirmado</span>
                            @else
                                <span class="badge bg-amber-50 text-amber-800 ring-amber-200">Por confirmar</span>
                            @endif
                        </p>
                    </div>

                    @unless ($pendingEmailChange)
                        <button type="button" data-toggle-target="email-form" aria-expanded="false"
                                class="btn btn-secondary btn-sm">Alterar</button>
                    @endunless
                </div>

                @if ($pendingEmailChange)
                    <div class="mt-4 rounded-xl bg-amber-50 px-4 py-3.5 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
                        <p>
                            Há um pedido de alteração para
                            <strong class="font-bold">{{ $pendingEmailChange->new_email }}</strong>,
                            à espera de confirmação. Expira {{ $pendingEmailChange->expires_at->diffForHumans() }}.
                        </p>
                        <form method="POST" action="{{ route('consumer.profile.email.cancel') }}" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-bold underline underline-offset-2">Cancelar pedido</button>
                        </form>
                    </div>
                @else
                    <form id="email-form" data-toggle-panel hidden
                          method="POST" action="{{ route('consumer.profile.email.request') }}"
                          class="mt-4 space-y-4 rounded-xl bg-ink-50 p-4" data-guard-submit>
                        @csrf
                        <x-field name="new_email" label="Novo endereço de email" type="email" required
                                 hint="O email atual mantém-se ativo até confirmares o novo." />
                        <x-field name="current_password" label="Palavra-passe atual" type="password" required
                                 autocomplete="current-password" id="email_current_password" />
                        <button type="submit" class="btn btn-primary btn-sm">Pedir alteração</button>
                    </form>
                @endif
            </section>

            {{-- Telefone --}}
            <section class="mt-7 border-t border-ink-100 pt-7" aria-labelledby="sec-telefone">
                <h3 id="sec-telefone" class="text-sm font-bold text-ink-800">Contacto telefónico</h3>
                <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-ink-600">
                    @if ($user->phone)
                        {{ $user->phone }}
                        @if ($user->phone_verified_at)
                            <span class="badge bg-brand-50 text-brand-700 ring-brand-200">Confirmado</span>
                        @else
                            <span class="badge bg-ink-100 text-ink-600 ring-ink-200">Por confirmar</span>
                        @endif
                    @else
                        <span class="text-ink-400">Ainda não indicaste um número.</span>
                    @endif
                </p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <form method="POST" action="{{ route('consumer.profile.phone.request') }}" class="space-y-3" data-guard-submit>
                        @csrf
                        <x-field name="phone" label="Número" type="tel" :value="$user->phone" placeholder="+351 912 345 678" />
                        <button type="submit" class="btn btn-secondary btn-sm">Enviar código</button>
                    </form>

                    <form method="POST" action="{{ route('consumer.profile.phone.confirm') }}" class="space-y-3" data-guard-submit>
                        @csrf
                        <x-field name="code" label="Código recebido" inputmode="numeric" maxlength="6" placeholder="000000" />
                        <button type="submit" class="btn btn-secondary btn-sm">Confirmar número</button>
                    </form>
                </div>

                <p class="hint">
                    A verificação por SMS está preparada mas o envio ainda não está ativo nesta
                    versão: o código é registado nos logs da aplicação em vez de ser enviado.
                </p>
            </section>

            {{-- Palavra-passe --}}
            <section class="mt-7 border-t border-ink-100 pt-7" aria-labelledby="sec-password">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 id="sec-password" class="text-sm font-bold text-ink-800">Palavra-passe</h3>
                        <p class="mt-1 text-sm text-ink-500">
                            Ao alterares, as sessões noutros dispositivos são terminadas.
                        </p>
                    </div>
                    <button type="button" data-toggle-target="password-form" aria-expanded="false"
                            class="btn btn-secondary btn-sm">Alterar</button>
                </div>

                <form id="password-form" data-toggle-panel hidden
                      method="POST" action="{{ route('consumer.profile.password') }}"
                      class="mt-4 space-y-4 rounded-xl bg-ink-50 p-4" data-guard-submit>
                    @csrf
                    @method('PATCH')
                    <x-field name="current_password" label="Palavra-passe atual" type="password" required autocomplete="current-password" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field name="password" label="Nova palavra-passe" type="password" required autocomplete="new-password"
                                 hint="Mínimo 10 caracteres, com letras e números." />
                        <x-field name="password_confirmation" label="Confirmar" type="password" required autocomplete="new-password" />
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Alterar palavra-passe</button>
                </form>
            </section>

            {{-- Contas ligadas --}}
            @if ($socialAccounts->isNotEmpty())
                <section class="mt-7 border-t border-ink-100 pt-7" aria-labelledby="sec-social">
                    <h3 id="sec-social" class="text-sm font-bold text-ink-800">Contas ligadas</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach ($socialAccounts as $account)
                            <li class="flex items-center justify-between gap-3 rounded-xl bg-ink-50 px-4 py-3 text-sm">
                                <span class="font-semibold text-ink-800">{{ ucfirst($account->provider) }}</span>
                                <span class="text-xs text-ink-500">{{ $account->provider_email }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

    <p class="text-center text-xs text-ink-500">
        Para exportar ou eliminar os teus dados, vai a
        <a href="{{ route('consumer.privacy') }}" class="font-bold text-brand-700 underline decoration-brand-300 underline-offset-2">Privacidade e dados</a>.
    </p>
</div>
@endsection
