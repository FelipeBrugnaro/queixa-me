@extends('layouts.app')

@section('content')
<div class="container-page py-8">

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl font-bold sm:text-4xl">Empresas de {{ $category->name }}</h1>
        @if ($category->description)
            <p class="mt-3 text-ink-600">{{ $category->description }}</p>
        @endif
    </header>

    @if ($companies->isEmpty())
        <x-empty-state
            title="Ainda não há empresas neste setor"
            description="Assim que existirem reclamações publicadas, as empresas aparecem aqui." />
    @else
        <p class="mb-4 text-sm text-ink-500">
            <strong class="font-semibold text-ink-800">{{ number_format($companies->total(), 0, ',', ' ') }}</strong>
            {{ $companies->total() === 1 ? 'empresa' : 'empresas' }}
        </p>

        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($companies as $company)
                <x-company-card :company="$company" />
            @endforeach
        </div>

        {{ $companies->links() }}
    @endif
</div>
@endsection
