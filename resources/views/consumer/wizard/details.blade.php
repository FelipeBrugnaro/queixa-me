@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')
<div class="container-narrow py-8">

    @include('consumer.wizard._progress', ['step' => $step])

    <header class="mb-8">
        <h1 class="text-2xl font-bold sm:text-3xl">Detalhes da reclamação</h1>
        <p class="mt-2 text-ink-600">
            Um bom assunto e os comprovativos certos aumentam muito a probabilidade de resolução.
        </p>
    </header>

    @if ($warnings)
        <div class="mb-6 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
            <p class="font-semibold">Verifica o texto antes de continuar</p>
            <ul class="mt-1.5 ml-4 list-disc space-y-1">
                @foreach ($warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <a href="{{ route('complaints.wizard.description', $complaint->uuid) }}" class="mt-2 inline-flex text-sm font-semibold underline underline-offset-2">
                Voltar e corrigir
            </a>
        </div>
    @endif

    <form method="POST" action="{{ route('complaints.wizard.details.store', $complaint->uuid) }}"
          enctype="multipart/form-data" class="card" data-guard-submit>
        @csrf
        <div class="card-body space-y-6">

            <div>
                <x-field name="title" label="Assunto" required :value="$complaint->title"
                         id="title"
                         minlength="{{ config('queixame.complaints.title_min') }}"
                         maxlength="{{ config('queixame.complaints.title_max') }}"
                         placeholder="ex.: Encomenda não entregue e sem resposta ao pedido de reembolso"
                         hint="Resume o problema numa frase. É este texto que aparece no título da página." />
                <p data-counter-for="title" data-counter-min="{{ config('queixame.complaints.title_min') }}" data-counter-max="{{ config('queixame.complaints.title_max') }}" class="hint text-right"></p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="category_id" label="Categoria" type="select"
                         :value="$complaint->category_id"
                         :options="$categories->pluck('name', 'id')->all()"
                         placeholder="Selecionar categoria" />

                <x-field name="occurred_on" label="Data da ocorrência" type="date"
                         :value="$complaint->occurred_on?->toDateString()"
                         max="{{ now()->toDateString() }}" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="purchase_reference" label="Nº de encomenda / contrato"
                         :value="$complaint->purchase_reference"
                         hint="Se existir. Facilita muito a identificação do teu processo." />

                <x-field name="amount_involved" label="Valor envolvido (€)" type="text"
                         inputmode="decimal"
                         :value="$complaint->amount_involved" />
            </div>

            <x-field name="desired_resolution" label="Como pretendes que a empresa resolva?" type="textarea" rows="5"
                     :value="$complaint->desired_resolution"
                     placeholder="ex.: Pretendo o reembolso integral de 149,90 € no prazo de 15 dias."
                     hint="Um pedido concreto é mais fácil de aceitar do que um pedido vago." />

            <x-field name="extra_info" label="Informações relevantes para a empresa" type="textarea" rows="4"
                     :value="$complaint->extra_info"
                     hint="Referências internas, datas de contactos anteriores, nomes de processos. Não incluas dados bancários." />

            {{-- Anexos --}}
            <fieldset>
                <legend class="label">Anexos</legend>

                <div class="rounded-xl border-2 border-dashed border-ink-200 p-5">
                    <input id="attachments" name="attachments[]" type="file" multiple
                           accept=".jpg,.jpeg,.png,.webp,.gif,.heic,.pdf"
                           data-file-input="attachment_preview"
                           data-max-size="{{ $attachmentConfig['max_size_kb'] }}"
                           data-max-files="{{ $attachmentConfig['max_files'] }}"
                           class="block w-full text-sm text-ink-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">

                    <p class="mt-3 text-xs text-ink-500">
                        Faturas, comprovativos, capturas de ecrã e fotografias.
                        Máximo {{ $attachmentConfig['max_files'] }} ficheiros, até
                        {{ round($attachmentConfig['max_size_kb'] / 1024) }} MB cada.
                        Formatos aceites: JPG, PNG, WEBP, GIF, HEIC e PDF.
                    </p>

                    <ul id="attachment_preview" class="mt-3 space-y-1.5"></ul>
                    <div id="attachments_errors" class="mt-2 hidden text-xs font-medium text-rose-600"></div>
                </div>

                <p class="hint">
                    Os anexos são privados: só a nossa equipa de moderação e a empresa visada lhes acedem.
                    Removemos automaticamente os metadados de localização das fotografias.
                </p>

                @error('attachments')<p class="error-text">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="error-text">{{ $message }}</p>@enderror

                @if ($complaint->attachments->isNotEmpty())
                    <ul class="mt-4 space-y-2">
                        @foreach ($complaint->attachments as $attachment)
                            <li class="flex items-center justify-between gap-3 rounded-lg bg-ink-50 px-3 py-2 text-sm">
                                <span class="min-w-0 truncate text-ink-700">{{ $attachment->original_name }}</span>
                                <span class="shrink-0 text-xs text-ink-400">{{ $attachment->humanSize() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </fieldset>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('complaints.wizard.description', $complaint->uuid) }}" class="btn btn-ghost">Voltar</a>
                <button type="submit" class="btn btn-primary">Continuar</button>
            </div>
        </div>
    </form>

    {{-- Remover anexos existentes: formulários próprios, fora do formulário principal --}}
    @if ($complaint->attachments->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($complaint->attachments as $attachment)
                <form method="POST" action="{{ route('complaints.wizard.attachment.destroy', [$complaint->uuid, $attachment->uuid]) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-sm text-rose-600 hover:bg-rose-50">
                        Remover {{ \Illuminate\Support\Str::limit($attachment->original_name, 24) }}
                    </button>
                </form>
            @endforeach
        </div>
    @endif
</div>
@endsection
