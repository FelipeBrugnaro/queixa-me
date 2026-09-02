@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')
<div class="container-narrow py-8">

    @include('consumer.wizard._progress', ['step' => $step])

    <header class="mb-8">
        <h1 class="text-2xl font-bold sm:text-3xl">Rever e submeter</h1>
        <p class="mt-2 text-ink-600">
            Confirma que está tudo certo. Depois de submeteres, a reclamação entra em análise.
        </p>
    </header>

    @if ($incomplete)
        <div class="mb-6 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-900 ring-1 ring-inset ring-rose-200">
            <p class="font-semibold">Faltam elementos obrigatórios</p>
            <p class="mt-1">Em falta: {{ implode(', ', $incomplete) }}.</p>
        </div>
    @endif

    @if ($warnings)
        <div class="mb-6 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
            <p class="font-semibold">Detetámos possíveis dados sensíveis no texto</p>
            <ul class="mt-1.5 ml-4 list-disc space-y-1">
                @foreach ($warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs">
                Podes submeter na mesma — a nossa equipa verifica antes de publicar —
                mas corrigir agora acelera bastante o processo.
            </p>
        </div>
    @endif

    {{-- Resumo --}}
    <div class="card">
        <div class="card-body space-y-6">

            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <x-company-avatar :company="$complaint->company" size="md" />
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ink-400">Empresa</p>
                        <p class="font-semibold">{{ $complaint->company?->name ?? $complaint->company_name_raw }}</p>
                    </div>
                </div>
                <a href="{{ route('complaints.create') }}" class="btn btn-ghost btn-sm">Editar</a>
            </div>

            <hr class="border-ink-100">

            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs uppercase tracking-wide text-ink-400">Assunto</p>
                    <p class="font-semibold">{{ $complaint->title ?: '—' }}</p>

                    <p class="mt-4 text-xs uppercase tracking-wide text-ink-400">Descrição</p>
                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink-600">{{ $complaint->description }}</p>

                    @if ($complaint->desired_resolution)
                        <p class="mt-4 text-xs uppercase tracking-wide text-ink-400">Resolução pretendida</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink-600">{{ $complaint->desired_resolution }}</p>
                    @endif

                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        @if ($complaint->occurred_on)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-ink-400">Data da ocorrência</dt>
                                <dd class="text-ink-700">{{ $complaint->occurred_on->translatedFormat('j/m/Y') }}</dd>
                            </div>
                        @endif
                        @if ($complaint->category)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-ink-400">Categoria</dt>
                                <dd class="text-ink-700">{{ $complaint->category->name }}</dd>
                            </div>
                        @endif
                        @if ($complaint->purchase_reference)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-ink-400">Nº de encomenda</dt>
                                <dd class="text-ink-700">{{ $complaint->purchase_reference }}</dd>
                            </div>
                        @endif
                        @if ($complaint->amount_involved)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-ink-400">Valor</dt>
                                <dd class="text-ink-700">{{ number_format((float) $complaint->amount_involved, 2, ',', ' ') }} €</dd>
                            </div>
                        @endif
                    </dl>
                </div>
                <a href="{{ route('complaints.wizard.details', $complaint->uuid) }}" class="btn btn-ghost btn-sm shrink-0">Editar</a>
            </div>

            @if ($complaint->attachments->isNotEmpty())
                <hr class="border-ink-100">
                <div>
                    <p class="text-xs uppercase tracking-wide text-ink-400">Anexos ({{ $complaint->attachments->count() }})</p>
                    <ul class="mt-2 space-y-1.5">
                        @foreach ($complaint->attachments as $attachment)
                            <li class="flex items-center justify-between gap-3 rounded-lg bg-ink-50 px-3 py-2 text-sm">
                                <span class="min-w-0 truncate text-ink-700">{{ $attachment->original_name }}</span>
                                <span class="shrink-0 text-xs text-ink-400">{{ $attachment->humanSize() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <hr class="border-ink-100">

            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-ink-400">Os teus dados (privados)</p>
                    @if ($complaint->contactDetails)
                        <p class="mt-1 text-sm text-ink-700">{{ $complaint->contactDetails->fullName() }}</p>
                        <p class="text-sm text-ink-500">{{ $complaint->contactDetails->email }}</p>
                        @if ($complaint->contactDetails->phone)
                            <p class="text-sm text-ink-500">{{ $complaint->contactDetails->phone }}</p>
                        @endif
                    @else
                        <p class="mt-1 text-sm text-rose-600">Por preencher</p>
                    @endif

                    <p class="mt-3 text-xs text-ink-500">
                        Identidade pública:
                        <strong class="font-semibold text-ink-700">
                            {{ $complaint->is_identity_public ? $complaint->user->publicDisplayName() : 'Reclamação anónima' }}
                        </strong>
                    </p>
                </div>
                <a href="{{ route('complaints.wizard.contact', $complaint->uuid) }}" class="btn btn-ghost btn-sm shrink-0">Editar</a>
            </div>
        </div>
    </div>

    {{-- Consentimentos --}}
    <form method="POST" action="{{ route('complaints.wizard.submit', $complaint->uuid) }}" class="card mt-6" data-guard-submit>
        @csrf
        <div class="card-body space-y-4">
            <h2 class="font-semibold">Consentimentos</h2>

            {{-- Este é o consentimento juridicamente mais relevante do fluxo:
                 é destacado visualmente e nunca vem pré-marcado. --}}
            <label class="flex items-start gap-3 rounded-xl bg-brand-50 p-4 text-sm ring-1 ring-inset ring-brand-200">
                <input type="checkbox" name="accept_data_transfer" value="1" class="checkbox" required>
                <span class="text-brand-900">
                    <strong class="font-semibold">
                        Consinto que os dados pessoais e demais informações fornecidas neste formulário
                        sejam transmitidos à entidade visada para efeitos de análise e resposta à presente reclamação.
                    </strong>
                    <span class="mt-1 block text-xs text-brand-800">
                        Sem este consentimento não podemos encaminhar a reclamação.
                        Podes retirá-lo mais tarde, mas isso encerra o processo.
                    </span>
                </span>
            </label>
            @error('accept_data_transfer')<p class="error-text">{{ $message }}</p>@enderror

            <label class="flex items-start gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="accept_terms" value="1" class="checkbox" required>
                <span>
                    Li e aceito os <a href="{{ route('legal.terms') }}" target="_blank" class="font-medium text-brand-700 underline underline-offset-2">Termos e Condições</a>,
                    a <a href="{{ route('legal.privacy') }}" target="_blank" class="font-medium text-brand-700 underline underline-offset-2">Política de Privacidade</a>
                    e a <a href="{{ route('legal.data-protection') }}" target="_blank" class="font-medium text-brand-700 underline underline-offset-2">Política de Proteção de Dados</a>.
                </span>
            </label>
            @error('accept_terms')<p class="error-text">{{ $message }}</p>@enderror

            <label class="flex items-start gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="confirm_truthful" value="1" class="checkbox" required>
                <span>
                    Declaro que a situação descrita ocorreu comigo e que a informação prestada é verdadeira.
                </span>
            </label>
            @error('confirm_truthful')<p class="error-text">{{ $message }}</p>@enderror

            <div class="rounded-xl bg-ink-100 px-4 py-3 text-xs leading-relaxed text-ink-600">
                O queixa.me não é uma entidade oficial de resolução de conflitos. Publicar aqui não
                substitui o Livro de Reclamações, as entidades reguladoras nem os tribunais, e não
                interrompe prazos legais.
            </div>

            <div class="flex items-center justify-between gap-3 pt-2">
                <a href="{{ route('complaints.wizard.contact', $complaint->uuid) }}" class="btn btn-ghost">Voltar</a>
                <button type="submit" class="btn btn-primary btn-lg" @disabled($incomplete)>
                    Submeter reclamação
                </button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('complaints.wizard.destroy', $complaint->uuid) }}" class="mt-4 text-center">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-xs text-ink-400 underline underline-offset-2 hover:text-rose-600">
            Eliminar este rascunho
        </button>
    </form>
</div>
@endsection
