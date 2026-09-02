@extends('layouts.panel')

@section('panel-heading')
    <h1 class="text-2xl font-bold">Denúncias</h1>
    <p class="mt-1 text-sm text-ink-600">
        Conteúdo assinalado por utilizadores ou pelas empresas visadas.
    </p>
@endsection

@section('panel')

    <nav aria-label="Filtrar por estado" class="mb-6 flex flex-wrap gap-2">
        @foreach ($statuses as $value => $label)
            <a href="{{ route('admin.reports.index', ['estado' => $value]) }}"
               class="badge {{ $activeStatus === $value ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-ink-700 ring-ink-200 hover:bg-ink-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if ($reports->isEmpty())
        <x-empty-state title="Sem denúncias neste estado" />
    @else
        <ul class="space-y-4">
            @foreach ($reports as $report)
                <li class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <span class="badge bg-rose-50 text-rose-700 ring-rose-200">{{ $report->reason->label() }}</span>
                                    <span class="text-ink-400">
                                        {{ $report->reporterCompany?->name ?? $report->reporter?->publicDisplayName() ?? 'Anónimo' }}
                                    </span>
                                </div>

                                <p class="mt-2 rounded-lg bg-ink-50 p-3 text-sm text-ink-700">{{ $report->details }}</p>

                                @if ($report->reportable)
                                    <a href="{{ $report->reportable instanceof \App\Domain\Complaints\Models\Complaint
                                                ? route('admin.moderation.show', $report->reportable->uuid)
                                                : '#' }}"
                                       class="mt-2 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800">
                                        Ver conteúdo denunciado
                                    </a>
                                @else
                                    <p class="mt-2 text-xs text-ink-400">O conteúdo denunciado já não existe.</p>
                                @endif
                            </div>

                            <span class="shrink-0 text-xs text-ink-400">{{ $report->created_at?->diffForHumans() }}</span>
                        </div>

                        @if ($report->status->value === 'open' || $report->status->value === 'in_review')
                            <form method="POST" action="{{ route('admin.reports.decide', $report) }}"
                                  class="mt-4 border-t border-ink-100 pt-4" data-guard-submit>
                                @csrf
                                <label for="notes_{{ $report->id }}" class="label">Fundamentação da decisão</label>
                                <textarea id="notes_{{ $report->id }}" name="notes" rows="2" required minlength="10" maxlength="1000"
                                          class="input textarea text-sm"></textarea>

                                <label class="mt-3 flex items-center gap-2 text-sm text-ink-700">
                                    <input type="checkbox" name="remove_content" value="1" class="checkbox">
                                    Remover o conteúdo (só se a denúncia proceder)
                                </label>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="submit" name="decision" value="uphold" class="btn btn-danger btn-sm">
                                        Procedente
                                    </button>
                                    <button type="submit" name="decision" value="dismiss" class="btn btn-secondary btn-sm">
                                        Improcedente
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="mt-4 border-t border-ink-100 pt-4 text-sm">
                                <p class="font-medium text-ink-800">
                                    {{ $report->status->label() }}
                                    <span class="text-xs font-normal text-ink-400">
                                        · {{ $report->resolved_at?->translatedFormat('j M Y') }}
                                    </span>
                                </p>
                                @if ($report->resolution_notes)
                                    <p class="mt-1 text-ink-600">{{ $report->resolution_notes }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        {{ $reports->links() }}
    @endif
@endsection
