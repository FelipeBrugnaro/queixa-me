@extends('layouts.app', ['hideBreadcrumbs' => true])

@php use App\Domain\Shared\Support\Countries; @endphp

@section('content')
<div class="container-narrow py-10">

    @include('consumer.wizard._progress', ['step' => $step])

    <header class="mb-8">
        <h1 class="text-3xl sm:text-4xl">Rever e submeter</h1>
        <p class="mt-3 text-[0.9375rem] leading-relaxed text-ink-600">
            Confirma que está tudo certo. Depois de submeteres, a reclamação entra em análise.
        </p>
    </header>

    @if ($incomplete)
        <div class="mb-6 rounded-2xl bg-rose-50 px-5 py-4 ring-1 ring-inset ring-rose-200">
            <p class="text-sm font-extrabold text-rose-900">Faltam elementos obrigatórios</p>
            <p class="mt-1 text-sm text-rose-800">Em falta: {{ implode(', ', $incomplete) }}.</p>
        </div>
    @endif

    @if ($warnings)
        <div class="mb-6 rounded-2xl bg-amber-50 px-5 py-4 ring-1 ring-inset ring-amber-200">
            <p class="text-sm font-extrabold text-amber-900">Detetámos possíveis dados sensíveis no texto</p>
            <ul class="mt-2 ml-4 list-disc space-y-1 text-sm text-amber-900">
                @foreach ($warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-amber-800">
                Podes submeter na mesma — a nossa equipa verifica antes de publicar — mas corrigir
                agora acelera bastante o processo.
            </p>
        </div>
    @endif

    {{-- Resumo --}}
    <div class="card overflow-hidden">
        <div class="divide-y divide-ink-100">

            <div class="flex items-start justify-between gap-4 p-5 sm:p-6">
                <div class="flex min-w-0 items-center gap-4">
                    <x-company-avatar :company="$complaint->company" size="lg" />
                    <div class="min-w-0">
                        <p class="eyebrow">Empresa</p>
                        <p class="mt-1 truncate text-base font-extrabold text-ink-900">
                            {{ $complaint->company?->name ?? $complaint->company_name_raw }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('complaints.create') }}" class="btn btn-ghost btn-sm shrink-0">Editar</a>
            </div>

            <div class="flex items-start justify-between gap-4 p-5 sm:p-6">
                <div class="min-w-0 flex-1">
                    <p class="eyebrow">Assunto</p>
                    <p class="mt-1 text-base font-extrabold leading-snug text-ink-900">{{ $complaint->title ?: '—' }}</p>

                    <p class="eyebrow mt-5">Descrição</p>
                    <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-ink-600">{{ $complaint->description }}</p>

                    @if ($complaint->desired_resolution)
                        <p class="eyebrow mt-5">Resolução pretendida</p>
                        <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-ink-600">{{ $complaint->desired_resolution }}</p>
                    @endif

                    <dl class="mt-5 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                        @if ($complaint->occurred_on)
                            <div>
                                <dt class="eyebrow">Ocorrência</dt>
                                <dd class="mt-0.5 font-semibold text-ink-800">{{ $complaint->occurred_on->translatedFormat('j/m/Y') }}</dd>
                            </div>
                        @endif
                        @if ($complaint->category)
                            <div>
                                <dt class="eyebrow">Categoria</dt>
                                <dd class="mt-0.5 font-semibold text-ink-800">{{ $complaint->category->name }}</dd>
                            </div>
                        @endif
                        @if ($complaint->attachments->isNotEmpty())
                            <div>
                                <dt class="eyebrow">Anexos</dt>
                                <dd class="mt-0.5 font-semibold text-ink-800">{{ $complaint->attachments->count() }} ficheiros</dd>
                            </div>
                        @endif
                    </dl>
                </div>
                <a href="{{ route('complaints.wizard.details', $complaint->uuid) }}" class="btn btn-ghost btn-sm shrink-0">Editar</a>
            </div>

            <div class="flex items-start justify-between gap-4 p-5 sm:p-6">
                <div class="min-w-0">
                    <p class="eyebrow">Os teus dados (privados)</p>

                    @if ($complaint->contactDetails)
                        <p class="mt-1.5 text-sm font-semibold text-ink-800">{{ $complaint->contactDetails->fullName() }}</p>
                        <p class="text-sm text-ink-500">{{ $complaint->contactDetails->email }}</p>
                        @if ($complaint->contactDetails->phone)
                            <p class="text-sm text-ink-500">{{ $complaint->contactDetails->phone }}</p>
                        @endif
                        @if ($complaint->contactDetails->country)
                            <p class="mt-1 text-sm text-ink-500">{{ Countries::label($complaint->contactDetails->country) }}</p>
                        @endif
                    @else
                        <p class="mt-1.5 text-sm font-semibold text-rose-600">Por preencher</p>
                    @endif

                    <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-ink-100 px-3 py-1.5">
                        <svg class="size-3.5 text-ink-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            @if ($complaint->is_identity_public)
                                <path d="M2.5 10s2.8-5 7.5-5 7.5 5 7.5 5-2.8 5-7.5 5-7.5-5-7.5-5Z"/><circle cx="10" cy="10" r="2"/>
                            @else
                                <rect x="4" y="8.5" width="12" height="8" rx="2"/><path d="M7 8.5V6a3 3 0 0 1 6 0v2.5"/>
                            @endif
                        </svg>
                        <span class="text-xs font-bold text-ink-700">
                            Assinada por
                            {{ $complaint->is_identity_public ? $complaint->user->publicDisplayName() : 'Anónimo' }}
                        </span>
                    </div>
                </div>
                <a href="{{ route('complaints.wizard.contact', $complaint->uuid) }}" class="btn btn-ghost btn-sm shrink-0">Editar</a>
            </div>
        </div>
    </div>

    {{-- Consentimentos e submissão --}}
    <form method="POST" action="{{ route('complaints.wizard.submit', $complaint->uuid) }}" class="card mt-6" data-guard-submit>
        @csrf
        <div class="card-body space-y-5">
            <h2 class="text-lg">Antes de submeter</h2>

            {{-- Consentimento juridicamente mais relevante do fluxo:
                 destacado, e nunca pré-marcado. --}}
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-brand-50 p-5 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-100/60">
                <input type="checkbox" name="accept_data_transfer" value="1" class="checkbox" required>
                <span class="text-sm text-brand-900">
                    <strong class="font-extrabold">
                        Consinto que os dados pessoais e demais informações fornecidas neste
                        formulário sejam transmitidos à entidade visada para efeitos de análise e
                        resposta à presente reclamação.
                    </strong>
                    <span class="mt-1.5 block text-xs leading-relaxed text-brand-800">
                        Sem este consentimento não podemos encaminhar a reclamação. Podes retirá-lo
                        mais tarde, mas isso encerra o processo.
                    </span>
                </span>
            </label>
            @error('accept_data_transfer')<p class="error-text">{{ $message }}</p>@enderror

            <label class="flex cursor-pointer items-start gap-3 text-sm text-ink-700">
                <input type="checkbox" name="accept_terms" value="1" class="checkbox" required>
                <span>
                    Li e aceito os <a href="{{ route('legal.terms') }}" target="_blank" class="font-bold text-brand-700 underline decoration-brand-300 underline-offset-2">Termos e Condições</a>,
                    a <a href="{{ route('legal.privacy') }}" target="_blank" class="font-bold text-brand-700 underline decoration-brand-300 underline-offset-2">Política de Privacidade</a>
                    e a <a href="{{ route('legal.data-protection') }}" target="_blank" class="font-bold text-brand-700 underline decoration-brand-300 underline-offset-2">Política de Proteção de Dados</a>.
                </span>
            </label>
            @error('accept_terms')<p class="error-text">{{ $message }}</p>@enderror

            <label class="flex cursor-pointer items-start gap-3 text-sm text-ink-700">
                <input type="checkbox" name="confirm_truthful" value="1" class="checkbox" required>
                <span>Declaro que a situação descrita ocorreu comigo e que a informação prestada é verdadeira.</span>
            </label>
            @error('confirm_truthful')<p class="error-text">{{ $message }}</p>@enderror

            {{-- Guardar no perfil: perguntado aqui, quando a pessoa já sabe
                 que dados escreveu, e não perdido a meio do formulário. --}}
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-ink-50 p-4 text-sm text-ink-700 transition hover:bg-ink-100">
                <input type="checkbox" name="save_to_profile" value="1" class="checkbox" checked>
                <span>
                    <span class="font-bold text-ink-900">Guardar estes dados no meu perfil</span>
                    <span class="mt-1 block text-xs leading-relaxed text-ink-500">
                        Da próxima vez aparecem preenchidos e não tens de os escrever outra vez.
                    </span>
                </span>
            </label>

            <p class="rounded-xl bg-ink-50 px-4 py-3 text-xs leading-relaxed text-ink-500">
                O queixa.me não é uma entidade oficial de resolução de conflitos. Publicar aqui não
                substitui o Livro de Reclamações, as entidades reguladoras nem os tribunais, e não
                interrompe prazos legais.
            </p>

            <div class="flex items-center justify-between gap-3 border-t border-ink-100 pt-6">
                <a href="{{ route('complaints.wizard.contact', $complaint->uuid) }}" class="btn btn-ghost">Voltar</a>
                <button type="submit" class="btn btn-primary btn-lg" @disabled($incomplete)>
                    Submeter reclamação
                </button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('complaints.wizard.destroy', $complaint->uuid) }}" class="mt-5 text-center">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-xs font-semibold text-ink-400 underline underline-offset-2 transition hover:text-rose-600">
            Eliminar este rascunho
        </button>
    </form>
</div>
@endsection
