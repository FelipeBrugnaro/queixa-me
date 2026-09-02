@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')
<div class="container-narrow py-8">

    @include('consumer.wizard._progress', ['step' => $step])

    <header class="mb-8">
        <h1 class="text-2xl font-bold sm:text-3xl">O que aconteceu?</h1>
        <p class="mt-2 text-ink-600">
            Escreve por ordem cronológica: o que compraste ou contrataste, quando, o que correu mal
            e o que já tentaste fazer para resolver.
        </p>
    </header>

    <div class="card mb-6">
        <div class="card-body flex items-center gap-3">
            <x-company-avatar :company="$complaint->company" size="md" />
            <div class="min-w-0 flex-1">
                <p class="text-xs uppercase tracking-wide text-ink-400">Reclamação sobre</p>
                <p class="truncate font-semibold text-ink-900">{{ $complaint->company?->name ?? $complaint->company_name_raw }}</p>
            </div>
            <a href="{{ route('complaints.create') }}" class="btn btn-ghost btn-sm">Alterar</a>
        </div>
    </div>

    <form method="POST" action="{{ route('complaints.wizard.description.store', $complaint->uuid) }}" class="card" data-guard-submit>
        @csrf
        <div class="card-body space-y-5">

            <div class="rounded-xl bg-brand-50 px-4 py-3 text-sm leading-relaxed text-brand-900 ring-1 ring-inset ring-brand-100">
                <p class="font-semibold">Antes de escreveres</p>
                <ul class="mt-1.5 ml-4 list-disc space-y-1 text-brand-800">
                    <li>Não escrevas aqui o teu NIF, IBAN, número de cartão ou morada — pedimos esses dados no passo seguinte, em privado.</li>
                    <li>Não identifiques funcionários pelo nome.</li>
                    <li>Descreve factos. Acusações que não consigas sustentar podem impedir a publicação.</li>
                </ul>
            </div>

            <div>
                <label for="description" class="label">
                    Descrição <span class="text-rose-600" aria-hidden="true">*</span>
                </label>
                <textarea id="description" name="description" rows="14" required
                          minlength="{{ $min }}" maxlength="{{ $max }}"
                          placeholder="No dia 12 de março comprei… A entrega estava prevista para… Contactei o apoio ao cliente a…"
                          class="input textarea @error('description') input-error @enderror">{{ old('description', $complaint->description) }}</textarea>
                <div class="mt-1.5 flex flex-wrap items-baseline justify-between gap-2">
                    <p class="text-xs text-ink-500">Quanto mais concreto fores, mais depressa a empresa consegue resolver.</p>
                    <p data-counter-for="description" data-counter-min="{{ $min }}" data-counter-max="{{ $max }}" class="text-xs text-ink-500"></p>
                </div>
                @error('description')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('complaints.create') }}" class="btn btn-ghost">Voltar</a>
                <button type="submit" class="btn btn-primary">Continuar</button>
            </div>
        </div>
    </form>

    <p class="mt-6 text-center text-xs text-ink-500">
        O rascunho é gravado no servidor: podes fechar esta página e continuar mais tarde.
    </p>
</div>
@endsection
