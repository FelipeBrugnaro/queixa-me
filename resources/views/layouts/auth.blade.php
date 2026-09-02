@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')
<div class="container-page py-10 sm:py-16">
    <div class="mx-auto w-full max-w-md">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold sm:text-3xl">@yield('auth-title')</h1>
            @hasSection('auth-subtitle')
                <p class="mt-2 text-sm leading-relaxed text-ink-600">@yield('auth-subtitle')</p>
            @endif
        </div>

        <div class="card">
            <div class="card-body">
                @yield('auth-body')
            </div>
        </div>

        @hasSection('auth-footer')
            <p class="mt-6 text-center text-sm text-ink-600">@yield('auth-footer')</p>
        @endif
    </div>
</div>
@endsection
