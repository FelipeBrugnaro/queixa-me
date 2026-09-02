@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Perfil da empresa</h1>
    <p class="mt-1 text-sm text-ink-600">
        Um perfil completo dá contexto a quem lê as reclamações e transmite que a empresa está presente.
    </p>
@endsection

@section('panel')
<div class="space-y-6">

    {{-- Logótipo --}}
    <section class="card" aria-labelledby="logotipo">
        <div class="card-body">
            <h2 id="logotipo" class="font-semibold">Logótipo</h2>
            <div class="mt-4 flex flex-wrap items-center gap-5">
                <x-company-avatar :company="$company" size="xl" />

                <form method="POST" action="{{ route('business.profile.logo') }}" enctype="multipart/form-data"
                      class="flex flex-wrap items-center gap-3">
                    @csrf
                    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/svg+xml" required
                           class="block text-sm text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                    <button type="submit" class="btn btn-secondary btn-sm">Carregar</button>
                </form>
            </div>
            @error('logo')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </section>

    {{-- Dados --}}
    <form method="POST" action="{{ route('business.profile.update') }}" class="card" data-guard-submit>
        @csrf
        @method('PATCH')
        <div class="card-body space-y-5">
            <h2 class="font-semibold">Identificação</h2>

            <div>
                <span class="label">Nome comercial</span>
                <p class="input bg-ink-50 text-ink-600">{{ $company->name }}</p>
                <p class="hint">
                    O nome não é editável aqui porque define o URL público e o histórico associado à
                    marca. Para o alterar, <a href="{{ route('contact') }}" class="underline underline-offset-2">contacta-nos</a>:
                    tratamos da mudança mantendo os endereços antigos a funcionar.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="legal_name" label="Denominação social" :value="$company->legal_name" />
                <x-field name="vat_number" label="NIF" :value="$company->vat_number" />
            </div>

            <x-field name="category_id" label="Setor" type="select"
                     :value="$company->category_id"
                     :options="$categories->pluck('name', 'id')->all()"
                     placeholder="Selecionar setor" />

            <x-field name="description" label="Descrição" type="textarea" rows="4"
                     :value="$company->description"
                     hint="Uma apresentação curta da empresa. Aparece na ficha pública." />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="website" label="Website" type="url" :value="$company->website" placeholder="https://…" />
                <x-field name="support_email" label="Email de apoio ao cliente" type="email" :value="$company->support_email" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="support_phone" label="Telefone de apoio" type="tel" :value="$company->support_phone" />
                <x-field name="postal_code" label="Código postal" :value="$company->postal_code" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="address" label="Morada" :value="$company->address" />
                <x-field name="locality" label="Localidade" :value="$company->locality" />
                <x-field name="district" label="Distrito" :value="$company->district" />
            </div>

            <hr class="border-ink-100">

            <h2 class="font-semibold">Presença nos motores de busca</h2>
            <x-field name="meta_description" label="Descrição para resultados de pesquisa" type="textarea" rows="2"
                     :value="$company->meta_description"
                     maxlength="300"
                     hint="Até 300 caracteres. Se deixares vazio, geramos automaticamente a partir dos indicadores." />

            <label class="flex items-start gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="accepts_complaints" value="1" class="checkbox" @checked($company->accepts_complaints)>
                <span>
                    Aceitar novas reclamações
                    <span class="mt-0.5 block text-xs text-ink-500">
                        Desativar não remove as reclamações existentes nem esconde a ficha.
                    </span>
                </span>
            </label>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">Guardar alterações</button>
            </div>
        </div>
    </form>

    {{-- Estado --}}
    <section class="card" aria-labelledby="estado">
        <div class="card-body">
            <h2 id="estado" class="font-semibold">Estado da ficha</h2>
            <dl class="mt-4 space-y-2.5 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-ink-500">Estado</dt>
                    <dd><span class="badge bg-ink-100 text-ink-700 ring-ink-200">{{ $company->status->label() }}</span></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-ink-500">Reivindicada</dt>
                    <dd class="font-medium">{{ $company->claimed_at?->translatedFormat('j M Y') ?? 'Não' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-ink-500">Visível nos motores de busca</dt>
                    <dd class="font-medium">{{ $company->is_indexable ? 'Sim' : 'Ainda não' }}</dd>
                </div>
            </dl>

            <a href="{{ $company->url() }}" class="btn btn-secondary mt-4">Ver ficha pública</a>
        </div>
    </section>
</div>
@endsection
