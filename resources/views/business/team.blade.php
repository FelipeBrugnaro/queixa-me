@extends('layouts.panel')

@php use App\Domain\Companies\Enums\CompanyRole; @endphp

@section('panel-heading')
    <h1 class="text-2xl font-bold">Equipa</h1>
    <p class="mt-1 text-sm text-ink-600">
        Quem tem acesso às reclamações e aos dados pessoais dos consumidores.
    </p>
@endsection

@section('panel')
<div class="space-y-6">

    <section class="card" aria-labelledby="membros">
        <div class="card-body">
            <h2 id="membros" class="font-semibold">Membros com acesso</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <caption class="sr-only">Membros da equipa de {{ $company->name }}</caption>
                    <thead class="text-left text-xs uppercase tracking-wide text-ink-500">
                        <tr>
                            <th scope="col" class="py-2 font-semibold">Pessoa</th>
                            <th scope="col" class="py-2 font-semibold">Função</th>
                            <th scope="col" class="py-2 font-semibold">Estado</th>
                            <th scope="col" class="py-2 text-right font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($members as $member)
                            @php $role = CompanyRole::tryFrom((string) $member->pivot->role); @endphp
                            <tr>
                                <td class="py-3">
                                    <p class="font-medium text-ink-900">{{ $member->name }}</p>
                                    <p class="text-xs text-ink-500">{{ $member->email }}</p>
                                    @if ($member->pivot->job_title)
                                        <p class="text-xs text-ink-400">{{ $member->pivot->job_title }}</p>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <form method="POST" action="{{ route('business.team.update', $member) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="input py-1.5 text-sm">
                                            @foreach ($roles as $value => $label)
                                                <option value="{{ $value }}" @selected($role?->value === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="job_title" value="{{ $member->pivot->job_title }}">
                                        <button type="submit" class="btn btn-secondary btn-sm">Guardar</button>
                                    </form>
                                </td>
                                <td class="py-3">
                                    @if ($member->pivot->accepted_at)
                                        <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200">Ativo</span>
                                    @else
                                        <span class="badge bg-amber-50 text-amber-700 ring-amber-200">Convite pendente</span>
                                    @endif
                                </td>
                                <td class="py-3 text-right">
                                    <form method="POST" action="{{ route('business.team.destroy', $member) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-sm text-rose-600 hover:bg-rose-50">
                                            Remover
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Convidar --}}
    <form method="POST" action="{{ route('business.team.store') }}" class="card" data-guard-submit>
        @csrf
        <div class="card-body space-y-4">
            <h2 class="font-semibold">Convidar alguém</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="name" label="Nome" required />
                <x-field name="email" label="Email profissional" type="email" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="role" label="Função" type="select" required :options="$roles" />
                <x-field name="job_title" label="Cargo na empresa" placeholder="ex.: Gestor de Apoio ao Cliente" />
            </div>

            <div class="rounded-xl bg-ink-100 px-4 py-3 text-xs leading-relaxed text-ink-600">
                A pessoa convidada define a palavra-passe através de "Recuperar palavra-passe" —
                é assim que confirmamos que controla mesmo aquele endereço de email.
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">Convidar</button>
            </div>
        </div>
    </form>

    {{-- Permissões --}}
    <section class="card" aria-labelledby="permissoes">
        <div class="card-body">
            <h2 id="permissoes" class="font-semibold">O que cada função pode fazer</h2>
            <dl class="mt-4 space-y-4 text-sm">
                @foreach (CompanyRole::cases() as $case)
                    <div>
                        <dt class="font-medium text-ink-800">{{ $case->label() }}</dt>
                        <dd class="mt-1 text-ink-600">
                            @switch($case)
                                @case(CompanyRole::Owner)
                                    Tudo, incluindo gerir a equipa e o perfil da empresa.
                                    @break
                                @case(CompanyRole::Manager)
                                    Responder, enviar mensagens, ver estatísticas e editar o perfil. Não gere a equipa.
                                    @break
                                @default
                                    Ver e responder a reclamações e enviar mensagens privadas. Sem acesso a estatísticas nem ao perfil.
                            @endswitch
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
</div>
@endsection
