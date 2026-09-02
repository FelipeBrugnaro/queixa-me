@props(['company', 'rank' => null, 'showMetrics' => true])

<article class="card card-hover">
    <div class="card-body">
        <div class="flex items-start gap-4">
            @if ($rank !== null)
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-sm font-bold text-ink-600">
                    {{ $rank }}
                </span>
            @endif

            <x-company-avatar :company="$company" size="md" />

            <div class="min-w-0 flex-1">
                <h3 class="truncate text-base font-semibold">
                    <a href="{{ $company->url() }}" class="hover:text-brand-700">{{ $company->name }}</a>
                </h3>
                <p class="mt-0.5 truncate text-xs text-ink-500">
                    {{ $company->category?->name ?? 'Sem categoria' }}
                    @if ($company->district)
                        <span aria-hidden="true">·</span> {{ $company->district }}
                    @endif
                </p>
            </div>

            <x-index-badge :company="$company" :show-label="false" />
        </div>

        @if ($showMetrics)
            <dl class="mt-5 grid grid-cols-3 gap-3 border-t border-ink-100 pt-4 text-center">
                <div>
                    <dt class="text-[11px] font-medium uppercase tracking-wide text-ink-400">Reclamações</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-ink-900">{{ number_format($company->published_complaints_count, 0, ',', ' ') }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-medium uppercase tracking-wide text-ink-400">Resposta</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-ink-900">
                        {{ $company->response_rate !== null ? number_format($company->response_rate, 0, ',', '').'%' : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] font-medium uppercase tracking-wide text-ink-400">Resolução</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-ink-900">
                        {{ $company->resolution_rate !== null ? number_format($company->resolution_rate, 0, ',', '').'%' : '—' }}
                    </dd>
                </div>
            </dl>
        @endif
    </div>
</article>
