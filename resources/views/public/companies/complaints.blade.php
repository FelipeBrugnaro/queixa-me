@extends('layouts.app')

@section('content')
<div class="container-page py-8">

    <header class="card mb-8">
        <div class="card-body flex flex-wrap items-center gap-4">
            <x-company-avatar :company="$company" size="lg" />
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold">Reclamações sobre {{ $company->name }}</h1>
                <p class="mt-1 text-sm text-ink-500">
                    {{ number_format($company->published_complaints_count, 0, ',', ' ') }} reclamações publicadas
                </p>
            </div>
            <div class="flex items-center gap-3">
                <x-index-badge :company="$company" size="lg" />
                <a href="{{ $company->url() }}" class="btn btn-secondary">Ver ficha</a>
            </div>
        </div>
    </header>

    <nav aria-label="Filtrar por estado" class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('companies.complaints', $company->slug) }}"
           class="badge {{ ! $activeStage ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-ink-700 ring-ink-200 hover:bg-ink-50' }}">
            Todas
        </a>
        @foreach ($stages as $stage)
            <a href="{{ route('companies.complaints', ['company' => $company->slug, 'estado' => $stage->value]) }}"
               class="badge {{ $activeStage === $stage->value ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white text-ink-700 ring-ink-200 hover:bg-ink-50' }}">
                {{ $stage->label() }}
            </a>
        @endforeach
    </nav>

    @if ($complaints->isEmpty())
        <x-empty-state title="Nenhuma reclamação neste estado" />
    @else
        <div class="space-y-4">
            @foreach ($complaints as $complaint)
                <x-complaint-card :complaint="$complaint" :show-company="false" />
            @endforeach
        </div>

        {{ $complaints->links() }}
    @endif
</div>
@endsection
