@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Privacidade e dados pessoais</h1>
    <p class="mt-1 text-sm text-ink-600">Controla o que fazemos com os teus dados e exerce os teus direitos.</p>
@endsection

@section('panel')
<div class="space-y-6">

    @if ($outdated)
        <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
            <p class="font-semibold">Documentos atualizados</p>
            <p class="mt-1">
                Atualizámos {{ implode(', ', array_map(fn ($c) => $c->label(), $outdated)) }}.
                Serão apresentados para nova aceitação na próxima reclamação que submeteres.
            </p>
        </div>
    @endif

    {{-- O que é público --}}
    <section class="card" aria-labelledby="publico">
        <div class="card-body">
            <h2 id="publico" class="font-semibold">O que é visível publicamente</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-emerald-50 p-4 ring-1 ring-inset ring-emerald-200">
                    <p class="text-sm font-semibold text-emerald-900">Público</p>
                    <ul class="mt-2 ml-4 list-disc space-y-1 text-xs text-emerald-800">
                        <li>Nome público: <strong>{{ $user->public_name ?? '—' }}</strong></li>
                        <li>Texto das reclamações aprovadas</li>
                        <li>Categoria, distrito e datas</li>
                        <li>Avaliações que deste às empresas</li>
                    </ul>
                </div>
                <div class="rounded-xl bg-ink-100 p-4 ring-1 ring-inset ring-ink-200">
                    <p class="text-sm font-semibold text-ink-900">Nunca público</p>
                    <ul class="mt-2 ml-4 list-disc space-y-1 text-xs text-ink-600">
                        <li>Nome próprio, apelido e morada</li>
                        <li>Email, telefone e data de nascimento</li>
                        <li>Anexos das reclamações</li>
                        <li>Mensagens privadas</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Comunicações --}}
    <form method="POST" action="{{ route('consumer.privacy.marketing') }}" class="card">
        @csrf
        @method('PATCH')
        <div class="card-body">
            <h2 class="font-semibold">Comunicações por email</h2>
            <label class="mt-4 flex items-start gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="marketing_opt_in" value="1" class="checkbox" @checked($user->marketing_opt_in)>
                <span>
                    Quero receber notícias, novidades e conteúdos sobre direitos do consumidor.
                    <span class="mt-0.5 block text-xs text-ink-500">
                        As mensagens sobre as tuas reclamações continuam a ser enviadas — são necessárias ao serviço.
                    </span>
                </span>
            </label>
            <div class="mt-4">
                <button type="submit" class="btn btn-secondary btn-sm">Guardar preferência</button>
            </div>
        </div>
    </form>

    {{-- Direitos --}}
    <section class="card" aria-labelledby="direitos">
        <div class="card-body">
            <h2 id="direitos" class="font-semibold">Os teus direitos</h2>
            <p class="mt-1 text-sm text-ink-600">
                Tratamos qualquer pedido no prazo legal máximo de 30 dias.
            </p>

            @if ($openRequests->isNotEmpty())
                <ul class="mt-4 space-y-2">
                    @foreach ($openRequests as $request)
                        <li class="flex items-center justify-between gap-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">
                            <span>Pedido de {{ $request->type === 'export' ? 'exportação' : 'eliminação' }} em curso</span>
                            <span class="text-xs">até {{ $request->due_at?->translatedFormat('j M Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <form method="POST" action="{{ route('consumer.privacy.export') }}" class="rounded-xl bg-ink-50 p-4" data-guard-submit>
                    @csrf
                    <p class="text-sm font-semibold text-ink-900">Exportar os meus dados</p>
                    <p class="mt-1 text-xs text-ink-600">
                        Recebes um ficheiro com tudo o que temos sobre ti: conta, reclamações, mensagens e consentimentos.
                    </p>
                    <button type="submit" class="btn btn-secondary btn-sm mt-3">Pedir exportação</button>
                </form>

                <form method="POST" action="{{ route('consumer.privacy.delete') }}" class="rounded-xl bg-rose-50 p-4 ring-1 ring-inset ring-rose-200" data-guard-submit>
                    @csrf
                    <p class="text-sm font-semibold text-rose-900">Eliminar a minha conta</p>
                    <p class="mt-1 text-xs leading-relaxed text-rose-800">
                        Apagamos todos os teus dados pessoais e a ligação entre ti e as reclamações.
                        O texto das reclamações já publicadas e as respostas das empresas mantêm-se,
                        sem qualquer identificação tua — é isso que impede que o histórico público
                        seja apagado seletivamente.
                    </p>

                    <textarea name="reason" rows="2" maxlength="1000" placeholder="Motivo (opcional)"
                              class="input mt-3 text-sm"></textarea>

                    <label class="mt-3 flex items-start gap-2 text-xs text-rose-900">
                        <input type="checkbox" name="confirm" value="1" class="checkbox" required>
                        Compreendo que esta ação é irreversível.
                    </label>
                    @error('confirm')<p class="error-text">{{ $message }}</p>@enderror

                    <button type="submit" class="btn btn-danger btn-sm mt-3">Pedir eliminação</button>
                </form>
            </div>
        </div>
    </section>

    {{-- Histórico de consentimentos --}}
    <section class="card" aria-labelledby="consentimentos">
        <div class="card-body">
            <h2 id="consentimentos" class="font-semibold">Histórico de consentimentos</h2>
            <p class="mt-1 text-sm text-ink-600">
                Registamos cada aceitação com a versão do documento, a data e a origem — é assim que
                conseguimos demonstrar consentimento informado.
            </p>

            @if ($consents->isEmpty())
                <p class="mt-4 text-sm text-ink-500">Sem registos.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-ink-500">
                            <tr>
                                <th scope="col" class="py-2 font-semibold">Documento</th>
                                <th scope="col" class="py-2 font-semibold">Versão</th>
                                <th scope="col" class="py-2 font-semibold">Estado</th>
                                <th scope="col" class="py-2 font-semibold">Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($consents as $consent)
                                <tr>
                                    <td class="py-2.5 text-ink-800">{{ $consent->type->label() }}</td>
                                    <td class="py-2.5 text-ink-500">{{ $consent->document_version }}</td>
                                    <td class="py-2.5">
                                        @if ($consent->granted)
                                            <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200">Concedido</span>
                                        @else
                                            <span class="badge bg-ink-100 text-ink-600 ring-ink-200">Revogado</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-ink-500">{{ $consent->granted_at?->translatedFormat('j M Y, H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
