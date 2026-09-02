<?php

declare(strict_types=1);

namespace App\Http\Controllers\Business;

use App\Domain\Accounts\Enums\UserStatus;
use App\Domain\Accounts\Enums\UserType;
use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Enums\CompanyRole;
use App\Domain\Companies\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BusinessTeamController extends Controller
{
    public function index(Request $request): View
    {
        $company = $this->company($request);
        abort_unless($request->user()->canForCompany($company, 'company.users'), 403);

        $this->seo()->title('Equipa');

        return view('business.team', [
            'company' => $company,
            'members' => $company->members()->get(),
            'roles' => CompanyRole::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->company($request);
        abort_unless($request->user()->canForCompany($company, 'company.users'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'role' => ['required', Rule::enum(CompanyRole::class)],
            'job_title' => ['nullable', 'string', 'max:120'],
        ]);

        $email = mb_strtolower($data['email']);

        DB::transaction(function () use ($company, $data, $email, $request): void {
            $user = User::where('email', $email)->first();

            if ($user === null) {
                // Conta criada sem palavra-passe: o convidado define-a através
                // do fluxo normal de recuperação, que valida o email.
                $user = User::create([
                    'uuid' => (string) Str::uuid(),
                    'type' => UserType::Business,
                    'status' => UserStatus::Active,
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => null,
                    'country' => 'PT',
                ]);
            }

            $company->members()->syncWithoutDetaching([
                $user->id => [
                    'role' => $data['role'],
                    'job_title' => $data['job_title'] ?? null,
                    'invited_by_user_id' => $request->user()->id,
                    'invited_at' => now(),
                    'revoked_at' => null,
                ],
            ]);
        });

        return back()->with('success', 'Convite registado. Pede à pessoa para definir a palavra-passe em "Recuperar palavra-passe".');
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        $company = $this->company($request);
        abort_unless($request->user()->canForCompany($company, 'company.users'), 403);

        $data = $request->validate([
            'role' => ['required', Rule::enum(CompanyRole::class)],
            'job_title' => ['nullable', 'string', 'max:120'],
        ]);

        $this->assertNotLastOwner($company, $member, $data['role']);

        $company->members()->updateExistingPivot($member->id, [
            'role' => $data['role'],
            'job_title' => $data['job_title'] ?? null,
        ]);

        return back()->with('success', 'Permissões atualizadas.');
    }

    public function destroy(Request $request, User $member): RedirectResponse
    {
        $company = $this->company($request);
        abort_unless($request->user()->canForCompany($company, 'company.users'), 403);

        $this->assertNotLastOwner($company, $member, null);

        $company->members()->updateExistingPivot($member->id, ['revoked_at' => now()]);

        return back()->with('success', 'Acesso removido.');
    }

    /** Uma empresa nunca pode ficar sem ninguém que a possa gerir. */
    private function assertNotLastOwner(Company $company, User $member, ?string $newRole): void
    {
        $owners = $company->members()->wherePivot('role', CompanyRole::Owner->value)->pluck('users.id');

        $isLastOwner = $owners->count() === 1 && $owners->first() === $member->id;

        if ($isLastOwner && $newRole !== CompanyRole::Owner->value) {
            abort(422, 'Tem de existir pelo menos um proprietário da conta.');
        }
    }

    private function company(Request $request): Company
    {
        return $request->attributes->get('company');
    }
}
