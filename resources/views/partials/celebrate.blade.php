@php $celebrate = session('celebrate'); @endphp

@if ($celebrate)
    {{--
        Ecrã de confirmação.

        Submeter uma reclamação é o momento que justifica todo o esforço do
        formulário — uma faixa verde no topo da página desperdiça-o. Esta
        sobreposição sai sozinha ao fim de alguns segundos e fecha com um
        clique ou com Escape: confirma, não interrompe.
    --}}
    <div data-success-overlay
         class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/55 p-6 backdrop-blur-sm"
         role="status" aria-live="polite">

        <div class="modal-panel max-w-sm p-8 text-center">

            <div class="relative mx-auto flex size-20 items-center justify-center">
                {{-- Ondas que se expandem a partir do visto --}}
                <span class="absolute inset-0 rounded-full bg-brand-400"
                      style="animation: qm-ring 1.6s ease-out infinite"></span>
                <span class="absolute inset-0 rounded-full bg-brand-400"
                      style="animation: qm-ring 1.6s ease-out 0.5s infinite"></span>

                <span class="relative flex size-20 items-center justify-center rounded-full"
                      style="background: linear-gradient(140deg, var(--color-brand-400), var(--color-brand-700))">
                    {{-- O traço do visto é desenhado, não aparece de repente --}}
                    <svg class="size-10 text-white" viewBox="0 0 52 52" fill="none" aria-hidden="true">
                        <path d="M14 27.5 22.5 36 38 18"
                              stroke="currentColor" stroke-width="5"
                              stroke-linecap="round" stroke-linejoin="round"
                              stroke-dasharray="48" stroke-dashoffset="48"
                              style="animation: qm-draw 0.5s cubic-bezier(0.65, 0, 0.45, 1) 0.15s forwards"/>
                    </svg>
                </span>
            </div>

            <h2 class="animate-rise mt-7 text-2xl" style="animation-delay: 0.25s">
                {{ $celebrate['title'] ?? 'Feito' }}
            </h2>

            <p class="animate-rise mt-3 text-sm leading-relaxed text-ink-600" style="animation-delay: 0.35s">
                {{ $celebrate['message'] ?? '' }}
            </p>

            @if (! empty($celebrate['reference']))
                <p class="animate-rise mt-5 inline-flex items-center gap-2 rounded-full bg-ink-100 px-3.5 py-1.5"
                   style="animation-delay: 0.45s">
                    <span class="text-[0.6875rem] font-bold uppercase tracking-wide text-ink-400">Referência</span>
                    <span class="text-xs font-extrabold tabular-nums text-ink-800">{{ $celebrate['reference'] }}</span>
                </p>
            @endif

            <p class="animate-rise mt-6 text-xs text-ink-400" style="animation-delay: 0.55s">
                Toca para continuar
            </p>
        </div>
    </div>
@endif
