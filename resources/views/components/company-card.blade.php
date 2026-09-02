@props(['company', 'rank' => null, 'showMetrics' => true])

<article class="card card-hover group relative">
    <div class="card-body">
        <div class="flex items-start gap-4">
            @if ($rank !== null)
                {{-- A posição em serifa: lê-se como número de página de um
                     índice, não como distintivo de gamificação. --}}
                <span class="font-display w-6 shrink-0 pt-1 text-2xl leading-none text-ink-300">
                    {{ $rank }}
                </span>
            @endif

            <x-company-avatar :company="$company" size="md" />

            <div class="min-w-0 flex-1">
                <h3 class="truncate text-base font-semibold tracking-tight" style="font-family: var(--font-sans)">
                    <a href="{{ $company->url() }}" class="transition hover:text-brand-800">
                        <span class="absolute inset-0" aria-hidden="true"></span>
                        {{ $company->name }}
                    </a>
                </h3>
                <p class="mt-0.5 truncate text-xs text-ink-500">
                    {{ $company->category?->name ?? 'Sem categoria' }}
                    @if ($company->district)
                        <span class="text-ink-300" aria-hidden="true">/</span> {{ $company->district }}
                    @endif
                </p>
            </div>

            <x-index-badge :company="$company" :show-label="false" />
        </div>

        @if ($showMetrics)
            <dl class="mt-5 grid grid-cols-3 gap-4 border-t border-ink-100 pt-4">
                @foreach ([
                    ['Reclamações', number_format($company->published_complaints_count, 0, ',', ' ')],
                    ['Resposta', $company->response_rate !== null ? number_format($company->response_rate, 0, ',', '').'%' : '—'],
                    ['Resolução', $company->resolution_rate !== null ? number_format($company->resolution_rate, 0, ',', '').'%' : '—'],
                ] as [$label, $value])
                    <div>
                        <dt class="text-[0.6875rem] font-medium uppercase tracking-wide text-ink-400">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-ink-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</article>
