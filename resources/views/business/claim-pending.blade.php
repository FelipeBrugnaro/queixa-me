@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')
<div class="container-narrow py-16">
    <div class="card">
        <div class="card-body text-center">
            <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600" aria-hidden="true">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                </svg>
            </span>

            <h1 class="mt-5 text-2xl font-bold">Pedido em análise</h1>
            <p class="mx-auto mt-3 max-w-md text-ink-600">
                Estamos a validar a tua ligação a
                <strong class="font-semibold text-ink-800">{{ $claim->company?->name }}</strong>.
                Normalmente respondemos em 1 a 2 dias úteis.
            </p>

            <dl class="mx-auto mt-8 max-w-sm space-y-2.5 text-left text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-500">Empresa</dt>
                    <dd class="font-medium text-ink-800">{{ $claim->company?->name }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-500">Email indicado</dt>
                    <dd class="font-medium text-ink-800">{{ $claim->work_email }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-500">Submetido</dt>
                    <dd class="font-medium text-ink-800">{{ $claim->created_at?->translatedFormat('j M Y, H:i') }}</dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @if ($claim->company)
                    <a href="{{ $claim->company->url() }}" class="btn btn-secondary">Ver ficha pública</a>
                @endif
                <a href="{{ route('contact') }}" class="btn btn-ghost">Falar connosco</a>
            </div>
        </div>
    </div>

    <p class="mt-6 text-center text-xs leading-relaxed text-ink-500">
        Enquanto o pedido está em análise, a ficha da empresa continua visível e as reclamações
        continuam a ser publicadas normalmente. O que está em falta é apenas o teu acesso.
    </p>
</div>
@endsection
