@extends('layouts.app', ['hideBreadcrumbs' => true])

@section('content')
<div class="container-page py-12 sm:py-20">
    <div class="mx-auto w-full max-w-md">

        <div class="mb-8">
            <h1 class="text-3xl sm:text-[2.25rem]">@yield('auth-title')</h1>
            @hasSection('auth-subtitle')
                <p class="mt-3 text-[0.9375rem] leading-relaxed text-ink-600">@yield('auth-subtitle')</p>
            @endif
        </div>

        <div class="card">
            <div class="card-body">
                @yield('auth-body')
            </div>
        </div>

        @hasSection('auth-footer')
            <p class="mt-6 text-sm text-ink-600">@yield('auth-footer')</p>
        @endif
    </div>
</div>
@endsection
