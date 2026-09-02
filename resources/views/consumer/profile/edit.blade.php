@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Perfil</h1>
    <p class="mt-1 text-sm text-ink-600">
        Perfil completo em {{ $user->profileCompletion() }}%. Quanto mais completo, mais rápido é
        submeter uma reclamação.
    </p>
    <div class="mt-3 h-1.5 max-w-xs overflow-hidden rounded-full bg-ink-200">
        <div class="h-full rounded-full bg-brand-600" style="width: {{ $user->profileCompletion() }}%"></div>
    </div>
@endsection

@section('panel')
<div class="space-y-6">

    {{-- Fotografia --}}
    <section class="card" aria-labelledby="foto">
        <div class="card-body">
            <h2 id="foto" class="font-semibold">Fotografia de perfil</h2>
            <div class="mt-4 flex flex-wrap items-center gap-5">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="" class="size-20 rounded-2xl object-cover ring-1 ring-ink-200">
                @else
                    <span class="flex size-20 items-center justify-center rounded-2xl bg-brand-100 text-2xl font-bold text-brand-700" aria-hidden="true">
                        {{ $user->initials() }}
                    </span>
                @endif

                <form method="POST" action="{{ route('consumer.profile.avatar') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                    @csrf
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required
                           class="block text-sm text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                    <button type="submit" class="btn btn-secondary btn-sm">Carregar</button>
                </form>

                @if ($user->avatar_path)
                    <form method="POST" action="{{ route('consumer.profile.avatar.destroy') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm text-rose-600 hover:bg-rose-50">Remover</button>
                    </form>
                @endif
            </div>
            @error('avatar')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </section>

    {{-- Dados --}}
    <form method="POST" action="{{ route('consumer.profile.update') }}" class="card" data-guard-submit>
        @csrf
        @method('PATCH')
        <div class="card-body space-y-5">
            <h2 class="font-semibold">Dados pessoais</h2>

            <x-field name="public_name" label="Nome público" required :value="$user->public_name"
                     hint="É o único nome visível nas reclamações publicadas." />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="first_name" label="Nome próprio" required :value="$user->first_name" />
                <x-field name="last_name" label="Apelido" required :value="$user->last_name" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="birthdate" label="Data de nascimento" type="date"
                         :value="$user->birthdate?->toDateString()" max="{{ now()->toDateString() }}" />
                <x-field name="gender" label="Género" type="select" :value="$user->gender?->value" :options="$genders" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="country" label="País" :value="$user->country" maxlength="2" />
                <x-field name="district" label="Distrito" type="select" :value="$user->district"
                         :options="collect($districts)->mapWithKeys(fn ($d) => [$d => $d])->all()" />
                <x-field name="locality" label="Localidade" :value="$user->locality" />
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">Guardar alterações</button>
            </div>
        </div>
    </form>

    {{-- Email --}}
    <section class="card" aria-labelledby="email">
        <div class="card-body">
            <h2 id="email" class="font-semibold">Endereço de email</h2>
            <p class="mt-1 text-sm text-ink-600">
                Atual: <strong class="font-medium text-ink-900">{{ $user->email }}</strong>
                @if ($user->hasVerifiedEmail())
                    <span class="badge ml-2 bg-emerald-50 text-emerald-700 ring-emerald-200">Confirmado</span>
                @else
                    <span class="badge ml-2 bg-amber-50 text-amber-700 ring-amber-200">Por confirmar</span>
                @endif
            </p>

            @if ($pendingEmailChange)
                <div class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
                    <p>
                        Há um pedido de alteração para <strong>{{ $pendingEmailChange->new_email }}</strong>,
                        à espera de confirmação. Expira {{ $pendingEmailChange->expires_at->diffForHumans() }}.
                    </p>
                    <form method="POST" action="{{ route('consumer.profile.email.cancel') }}" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-semibold underline underline-offset-2">Cancelar pedido</button>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ route('consumer.profile.email.request') }}" class="mt-4 space-y-4" data-guard-submit>
                    @csrf
                    <x-field name="new_email" label="Novo endereço de email" type="email" required
                             hint="O email atual mantém-se ativo até confirmares o novo." />
                    <x-field name="current_password" label="Palavra-passe atual" type="password" required
                             autocomplete="current-password" id="email_current_password" />
                    <button type="submit" class="btn btn-secondary">Pedir alteração de email</button>
                </form>
            @endif
        </div>
    </section>

    {{-- Telefone --}}
    <section class="card" aria-labelledby="telefone">
        <div class="card-body">
            <h2 id="telefone" class="font-semibold">Contacto telefónico</h2>
            <p class="mt-1 text-sm text-ink-600">
                @if ($user->phone)
                    Atual: <strong class="font-medium text-ink-900">{{ $user->phone }}</strong>
                    @if ($user->phone_verified_at)
                        <span class="badge ml-2 bg-emerald-50 text-emerald-700 ring-emerald-200">Confirmado</span>
                    @else
                        <span class="badge ml-2 bg-ink-100 text-ink-600 ring-ink-200">Por confirmar</span>
                    @endif
                @else
                    Ainda não indicaste um número.
                @endif
            </p>

            <div class="mt-4 grid gap-6 sm:grid-cols-2">
                <form method="POST" action="{{ route('consumer.profile.phone.request') }}" class="space-y-3" data-guard-submit>
                    @csrf
                    <x-field name="phone" label="Número de telefone" type="tel" :value="$user->phone"
                             placeholder="+351 912 345 678" />
                    <button type="submit" class="btn btn-secondary btn-sm">Enviar código por SMS</button>
                </form>

                <form method="POST" action="{{ route('consumer.profile.phone.confirm') }}" class="space-y-3" data-guard-submit>
                    @csrf
                    <x-field name="code" label="Código de confirmação" inputmode="numeric" maxlength="6"
                             placeholder="000000" />
                    <button type="submit" class="btn btn-secondary btn-sm">Confirmar número</button>
                </form>
            </div>

            <p class="mt-3 text-xs text-ink-500">
                A verificação por SMS está preparada mas o envio ainda não está ativo nesta versão:
                o código é registado nos logs da aplicação em vez de ser enviado.
            </p>
        </div>
    </section>

    {{-- Palavra-passe --}}
    <form method="POST" action="{{ route('consumer.profile.password') }}" class="card" data-guard-submit>
        @csrf
        @method('PATCH')
        <div class="card-body space-y-4">
            <h2 class="font-semibold">Palavra-passe</h2>
            <x-field name="current_password" label="Palavra-passe atual" type="password" required autocomplete="current-password" />
            <x-field name="password" label="Nova palavra-passe" type="password" required autocomplete="new-password"
                     hint="Mínimo 10 caracteres, com letras e números." />
            <x-field name="password_confirmation" label="Confirmar nova palavra-passe" type="password" required autocomplete="new-password" />
            <p class="text-xs text-ink-500">Ao alterares, as sessões noutros dispositivos são terminadas.</p>
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">Alterar palavra-passe</button>
            </div>
        </div>
    </form>

    {{-- Contas ligadas --}}
    @if ($socialAccounts->isNotEmpty())
        <section class="card" aria-labelledby="social">
            <div class="card-body">
                <h2 id="social" class="font-semibold">Contas ligadas</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($socialAccounts as $account)
                        <li class="flex items-center justify-between gap-3 rounded-lg bg-ink-50 px-3 py-2 text-sm">
                            <span class="font-medium text-ink-800">{{ ucfirst($account->provider) }}</span>
                            <span class="text-xs text-ink-500">{{ $account->provider_email }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</div>
@endsection
