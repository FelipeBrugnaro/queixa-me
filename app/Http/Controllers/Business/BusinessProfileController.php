<?php

declare(strict_types=1);

namespace App\Http\Controllers\Business;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyCategory;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BusinessProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $company = $this->company($request);

        $this->seo()->title('Perfil da empresa');

        return view('business.profile', [
            'company' => $company,
            'categories' => CompanyCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->company($request);
        abort_unless($request->user()->canForCompany($company, 'company.manage'), 403);

        $data = $request->validate([
            'legal_name' => ['nullable', 'string', 'max:190'],
            'category_id' => ['nullable', 'integer', 'exists:company_categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:190'],
            'support_email' => ['nullable', 'email:rfc', 'max:190'],
            'support_phone' => ['nullable', 'string', 'max:32'],
            'vat_number' => ['nullable', 'string', 'max:32'],
            'district' => ['nullable', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:190'],
            'postal_code' => ['nullable', 'string', 'max:16'],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ]);

        // O NOME da empresa não é editável aqui de propósito: mudá-lo altera
        // o URL público e o histórico de reclamações associado à marca. Um
        // pedido de mudança de nome passa pela moderação, que trata do slug
        // e do redirecionamento 301.
        $company->update($data);

        return back()->with('success', 'Perfil da empresa atualizado.');
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        $company = $this->company($request);
        abort_unless($request->user()->canForCompany($company, 'company.manage'), 403);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:1024', 'dimensions:max_width=2000,max_height=2000'],
        ], [], ['logo' => 'logótipo']);

        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $path = $request->file('logo')->store('logos/'.$company->uuid, 'public');
        $company->forceFill(['logo_path' => $path])->save();

        return back()->with('success', 'Logótipo atualizado.');
    }

    private function company(Request $request): Company
    {
        return $request->attributes->get('company');
    }
}
