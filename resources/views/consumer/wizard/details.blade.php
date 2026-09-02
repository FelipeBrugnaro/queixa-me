@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')
<div class="container-narrow py-10">

    @include('consumer.wizard._progress', ['step' => $step])

    <header class="mb-8">
        <h1 class="text-3xl sm:text-4xl">Detalhes da reclamação</h1>
        <p class="mt-3 text-[0.9375rem] leading-relaxed text-ink-600">
            Um bom assunto e os comprovativos certos aumentam muito a probabilidade de resolução.
        </p>
    </header>

    @if ($warnings)
        <div class="mb-6 overflow-hidden rounded-2xl bg-amber-50 ring-1 ring-inset ring-amber-200">
            <div class="px-5 py-4">
                <p class="text-sm font-extrabold text-amber-900">Verifica o texto antes de continuar</p>
                <ul class="mt-2 ml-4 list-disc space-y-1 text-sm text-amber-900">
                    @foreach ($warnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
                <a href="{{ route('complaints.wizard.description', $complaint->uuid) }}"
                   class="mt-3 inline-flex text-sm font-bold text-amber-900 underline underline-offset-2">
                    Voltar e corrigir
                </a>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('complaints.wizard.details.store', $complaint->uuid) }}"
          enctype="multipart/form-data" class="card" data-guard-submit>
        @csrf
        <div class="card-body space-y-7">

            <div>
                <x-field name="title" label="Assunto" required :value="$complaint->title"
                         id="title"
                         minlength="{{ config('queixame.complaints.title_min') }}"
                         maxlength="{{ config('queixame.complaints.title_max') }}"
                         placeholder="ex.: Encomenda não entregue e sem resposta ao pedido de reembolso"
                         hint="Resume o problema numa frase. É este texto que aparece como título da página." />
                <p data-counter-for="title"
                   data-counter-min="{{ config('queixame.complaints.title_min') }}"
                   data-counter-max="{{ config('queixame.complaints.title_max') }}"
                   class="mt-2 text-right text-xs text-ink-400"></p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="category_id" label="Categoria" type="select"
                         :value="$complaint->category_id"
                         :options="$categories->pluck('name', 'id')->all()"
                         placeholder="Selecionar categoria" />

                <x-field name="occurred_on" label="Data da ocorrência" type="date"
                         :value="$complaint->occurred_on?->toDateString()"
                         max="{{ now()->toDateString() }}" />
            </div>

            <x-field name="desired_resolution" label="Como pretendes que a empresa resolva?" type="textarea" rows="5"
                     :value="$complaint->desired_resolution"
                     placeholder="ex.: Pretendo o reembolso integral do valor pago, no prazo de 15 dias."
                     hint="Um pedido concreto é muito mais fácil de aceitar do que um pedido vago." />

            {{-- Anexos --}}
            <fieldset>
                <legend class="label">Anexos</legend>

                <div class="rounded-2xl border-2 border-dashed border-ink-200 bg-ink-50/50 p-5 transition hover:border-ink-300">
                    <input id="attachments" name="attachments[]" type="file" multiple
                           accept=".jpg,.jpeg,.png,.webp,.gif,.heic,.pdf"
                           data-file-input="attachment_preview"
                           data-max-size="{{ $attachmentConfig['max_size_kb'] }}"
                           data-max-files="{{ $attachmentConfig['max_files'] }}"
                           class="block w-full text-sm text-ink-600 file:mr-4 file:rounded-xl file:border-0 file:bg-brand-600 file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-white hover:file:bg-brand-700">

                    <p class="mt-3 text-xs leading-relaxed text-ink-500">
                        Faturas, comprovativos, capturas de ecrã e fotografias.
                        Máximo {{ $attachmentConfig['max_files'] }} ficheiros, até
                        {{ round($attachmentConfig['max_size_kb'] / 1024) }} MB cada.
                        Formatos: JPG, PNG, WEBP, GIF, HEIC e PDF.
                    </p>

                    <ul id="attachment_preview" class="mt-3 space-y-2"></ul>
                    <div id="attachments_errors" class="mt-2 hidden text-xs font-semibold text-rose-600"></div>
                </div>

                <p class="hint">
                    Os anexos são privados: só a nossa equipa de moderação e a empresa visada lhes
                    acedem. Removemos automaticamente os metadados de localização das fotografias.
                </p>

                @error('attachments')<p class="error-text">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="error-text">{{ $message }}</p>@enderror

                @if ($complaint->attachments->isNotEmpty())
                    <ul class="mt-4 space-y-2">
                        @foreach ($complaint->attachments as $attachment)
                            <li class="flex items-center justify-between gap-3 rounded-xl bg-ink-50 px-4 py-3 text-sm">
                                <span class="min-w-0 truncate font-medium text-ink-700">{{ $attachment->original_name }}</span>
                                <span class="flex shrink-0 items-center gap-3">
                                    <span class="text-xs text-ink-400">{{ $attachment->humanSize() }}</span>
                                    <button type="submit"
                                            form="remove-{{ $attachment->uuid }}"
                                            class="text-xs font-bold text-rose-600 transition hover:text-rose-700">
                                        Remover
                                    </button>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </fieldset>

            <div class="flex items-center justify-between gap-3 border-t border-ink-100 pt-6">
                <a href="{{ route('complaints.wizard.description', $complaint->uuid) }}" class="btn btn-ghost">Voltar</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    Continuar
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 10h11M11 5.5 15.5 10 11 14.5"/>
                    </svg>
                </button>
            </div>
        </div>
    </form>

    {{-- Formulários de remoção fora do formulário principal: aninhá-los seria
         HTML inválido, e o atributo form= liga cada botão ao seu. --}}
    @foreach ($complaint->attachments as $attachment)
        <form id="remove-{{ $attachment->uuid }}" method="POST"
              action="{{ route('complaints.wizard.attachment.destroy', [$complaint->uuid, $attachment->uuid]) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</div>
@endsection
