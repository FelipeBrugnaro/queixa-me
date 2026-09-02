<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Actions\MergeCompanies;
use App\Domain\Companies\Enums\CompanyRole;
use App\Domain\Companies\Enums\CompanyStatus;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyCategory;
use App\Domain\Companies\Models\CompanyClaim;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdminCompanyController extends Controller
{
    public function index(Request $request): View
    {
        $this->seo()->title('Empresas');

        $companies = Company::query()
            ->with('category:id,name')
            ->when($request->query('estado'), fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($request->query('q'), fn (Builder $q, string $term) => $q->search($term))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('published_complaints_count')
            ->paginate(25)
            ->withQueryString();

        return view('admin.companies.index', [
            'companies' => $companies,
            'statuses' => CompanyStatus::options(),
            'claims' => CompanyClaim::where('status', 'pending')
                ->with(['company:id,name,slug,website', 'user:id,name,email'])
                ->latest()
                ->get(),
        ]);
    }

    public function edit(Company $company): View
    {
        $this->seo()->title('Editar '.$company->name);

        return view('admin.companies.edit', [
            'company' => $company->load(['category', 'members']),
            'categories' => CompanyCategory::orderBy('name')->get(['id', 'name']),
            'statuses' => CompanyStatus::options(),
            'duplicateCandidates' => Company::where('id', '!=', $company->id)
                ->search(mb_substr($company->name, 0, 12))
                ->limit(10)
                ->get(['id', 'name', 'slug', 'status', 'published_complaints_count']),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'legal_name' => ['nullable', 'string', 'max:190'],
            'category_id' => ['nullable', 'integer', 'exists:company_categories,id'],
            'status' => ['required', Rule::enum(CompanyStatus::class)],
            'website' => ['nullable', 'url', 'max:190'],
            'vat_number' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:2000'],
            'district' => ['nullable', 'string', 'max:120'],
            'meta_title' => ['nullable', 'string', 'max:190'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'accepts_complaints' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($company, $data): void {
            // Mudar o nome muda o slug — e o slug antigo tem de continuar a
            // funcionar, ou perdem-se todas as ligações externas já indexadas.
            if ($data['name'] !== $company->name) {
                $newSlug = Company::generateSlug($data['name'], $company->id);

                if ($newSlug !== $company->slug) {
                    $company->slugs()->firstOrCreate(['slug' => $company->slug], ['created_at' => now()]);
                    $data['slug'] = $newSlug;
                }
            }

            $company->update($data + ['accepts_complaints' => (bool) ($data['accepts_complaints'] ?? false)]);
            $company->forceFill(['is_indexable' => $company->shouldBeIndexed()])->save();
        });

        return back()->with('success', 'Empresa atualizada.');
    }

    public function approve(Request $request, Company $company): RedirectResponse
    {
        $company->forceFill([
            'status' => CompanyStatus::Active,
            'verified_at' => $request->boolean('verified') ? now() : $company->verified_at,
        ])->save();

        $company->forceFill(['is_indexable' => $company->shouldBeIndexed()])->save();

        return back()->with('success', 'Empresa aprovada e visível no portal.');
    }

    public function merge(Request $request, Company $company, MergeCompanies $action): RedirectResponse
    {
        $data = $request->validate([
            'target_id' => ['required', 'integer', 'exists:companies,id', 'different:'.$company->id],
        ], [], ['target_id' => 'empresa de destino']);

        try {
            $target = $action->handle($company, Company::findOrFail($data['target_id']));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.companies.edit', $target)
            ->with('success', 'Empresas fundidas. Os URLs antigos passam a redirecionar para esta ficha.');
    }

    public function suspend(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $company->forceFill([
            'status' => CompanyStatus::Suspended,
            'suspended_at' => now(),
            'is_indexable' => false,
        ])->save();

        return back()->with('success', 'Empresa suspensa: '.$data['reason']);
    }

    public function decideClaim(Request $request, CompanyClaim $claim): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($claim, $data, $request): void {
            $claim->update([
                'status' => $data['decision'] === 'approve' ? 'approved' : 'rejected',
                'decision_notes' => $data['notes'] ?? null,
                'reviewed_by_user_id' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($data['decision'] !== 'approve') {
                return;
            }

            $company = $claim->company;

            $company->members()->syncWithoutDetaching([
                $claim->user_id => [
                    'role' => CompanyRole::Owner->value,
                    'accepted_at' => now(),
                    'revoked_at' => null,
                ],
            ]);

            $company->forceFill([
                'claimed_at' => $company->claimed_at ?? now(),
                'status' => $company->status === CompanyStatus::Pending ? CompanyStatus::Active : $company->status,
            ])->save();
        });

        return back()->with('success', 'Reivindicação processada.');
    }
}
