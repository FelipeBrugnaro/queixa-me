@extends('layouts.panel')

@section('panel-heading')
    <a href="{{ route('admin.companies.index') }}" class="text-sm font-medium text-ink-500 hover:text-ink-800">
        <span aria-hidden="true">&larr;</span> Empresas
    </a>
    <div class="mt-2 flex flex-wrap items-center gap-3">
        <x-company-avatar :company="$company" size="lg" />
        <div>
            <h1 class="text-2xl font-bold">{{ $company->name }}</h1>
            <p class="text-sm text-ink-500">
                {{ $company->status->label() }}
                <span aria-hidden="true">·</span> {{ $company->published_complaints_count }} reclamações publicadas
            </p>
        </div>
    </div>
@endsection

@section('panel')
<div class="space-y-6">

    @if ($company->status->value === 'pending')
        <form method="POST" action="{{ route('admin.companies.approve', $company) }}"
              class="card ring-amber-200" data-guard-submit>
            @csrf
            <div class="card-body">
                <h2 class="font-semibold text-amber-900">Ficha por validar</h2>
                <p class="mt-1 text-sm text-ink-600">
                    Foi criada por um utilizador ao submeter uma reclamação. Confirma que a entidade
                    existe e que não é duplicado antes de a tornar pública.
                </p>
                <label class="mt-3 flex items-center gap-2 text-sm text-ink-700">
                    <input type="checkbox" name="verified" value="1" class="checkbox">
                    Marcar também como verificada
                </label>
                <button type="submit" class="btn btn-primary mt-4">Aprovar e publicar ficha</button>
            </div>
        </form>
    @endif

    {{-- Dados --}}
    <form method="POST" action="{{ route('admin.companies.update', $company) }}" class="card" data-guard-submit>
        @csrf
        @method('PATCH')
        <div class="card-body space-y-5">
            <h2 class="font-semibold">Dados da ficha</h2>

            <x-field name="name" label="Nome comercial" required :value="$company->name"
                     hint="Alterar o nome gera um novo endereço público. O anterior passa automaticamente a redirecionar." />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="legal_name" label="Denominação social" :value="$company->legal_name" />
                <x-field name="vat_number" label="NIF" :value="$company->vat_number" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="status" label="Estado" type="select" required
                         :value="$company->status->value" :options="$statuses" />
                <x-field name="category_id" label="Setor" type="select"
                         :value="$company->category_id"
                         :options="$categories->pluck('name', 'id')->all()"
                         placeholder="Sem categoria" />
            </div>

            <x-field name="description" label="Descrição" type="textarea" rows="3" :value="$company->description" />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="website" label="Website" type="url" :value="$company->website" />
                <x-field name="district" label="Distrito" :value="$company->district" />
            </div>

            <hr class="border-ink-100">

            <h2 class="font-semibold">SEO</h2>
            <x-field name="meta_title" label="Meta title" :value="$company->meta_title" maxlength="190" />
            <x-field name="meta_description" label="Meta description" type="textarea" rows="2"
                     :value="$company->meta_description" maxlength="300" />

            <label class="flex items-center gap-2 text-sm text-ink-700">
                <input type="checkbox" name="accepts_complaints" value="1" class="checkbox" @checked($company->accepts_complaints)>
                Aceita novas reclamações
            </label>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </form>

    {{-- Fusão de duplicados --}}
    @if ($duplicateCandidates->isNotEmpty())
        <form method="POST" action="{{ route('admin.companies.merge', $company) }}" class="card" data-guard-submit>
            @csrf
            <div class="card-body">
                <h2 class="font-semibold">Fundir com outra ficha</h2>
                <p class="mt-1 text-sm text-ink-600">
                    As reclamações passam para a ficha de destino e este endereço fica a redirecionar
                    permanentemente. Nada se perde e nenhuma ligação externa quebra.
                </p>

                <label for="target_id" class="label mt-4">Ficha de destino</label>
                <select id="target_id" name="target_id" required class="input">
                    <option value="">Selecionar…</option>
                    @foreach ($duplicateCandidates as $candidate)
                        <option value="{{ $candidate->id }}">
                            {{ $candidate->name }} ({{ $candidate->published_complaints_count }} reclamações · {{ $candidate->status->label() }})
                        </option>
                    @endforeach
                </select>
                @error('target_id')<p class="error-text">{{ $message }}</p>@enderror

                <button type="submit" class="btn btn-secondary mt-4">
                    Fundir «{{ $company->name }}» na ficha selecionada
                </button>
            </div>
        </form>
    @endif

    {{-- Suspender --}}
    <form method="POST" action="{{ route('admin.companies.suspend', $company) }}" class="card ring-rose-200" data-guard-submit>
        @csrf
        <div class="card-body">
            <h2 class="font-semibold text-rose-900">Suspender empresa</h2>
            <p class="mt-1 text-xs text-ink-500">
                Retira a ficha do índice e do diretório. Usar em casos de abuso da conta empresarial.
            </p>
            <input type="text" name="reason" required maxlength="500" placeholder="Motivo da suspensão"
                   class="input mt-3 text-sm">
            <button type="submit" class="btn btn-danger mt-3">Suspender</button>
        </div>
    </form>

    {{-- Gestores --}}
    <section class="card" aria-labelledby="gestores">
        <div class="card-body">
            <h2 id="gestores" class="font-semibold">Gestores com acesso</h2>
            @if ($company->members->isEmpty())
                <p class="mt-2 text-sm text-ink-500">Ficha ainda não reivindicada.</p>
            @else
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($company->members as $member)
                        <li class="flex items-center justify-between gap-3 rounded-lg bg-ink-50 px-3 py-2">
                            <span>
                                <span class="font-medium text-ink-800">{{ $member->name }}</span>
                                <span class="text-xs text-ink-500"> · {{ $member->email }}</span>
                            </span>
                            <span class="badge bg-white text-ink-600 ring-ink-200">{{ $member->pivot->role }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</div>
@endsection
