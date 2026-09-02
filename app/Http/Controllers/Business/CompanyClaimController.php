<?php

declare(strict_types=1);

namespace App\Http\Controllers\Business;

use App\Domain\Companies\Enums\CompanyRole;
use App\Domain\Companies\Enums\CompanyStatus;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyClaim;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reivindicação da ficha de empresa.
 *
 * PROBLEMA: quem garante que quem diz gerir a "Empresa X" a gere mesmo? Uma
 * reivindicação aceite dá acesso a dados pessoais de consumidores e ao
 * direito de responder em nome da marca. É o ponto mais sensível do portal.
 *
 * SOLUÇÃO EM CAMADAS:
 *  1. Sinal automático forte — email profissional no domínio do site da
 *     empresa. Quando bate certo, a confiança é alta.
 *  2. Revisão humana obrigatória para tudo o resto (Gmail, domínio diferente,
 *     empresa sem website registado).
 *  3. Nunca aprovação automática só por NIF: o NIF é público em Portugal.
 */
class CompanyClaimController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->primaryCompany()) {
            return redirect()->route('business.dashboard');
        }

        if ($this->pendingClaim($request)) {
            return redirect()->route('business.claim.pending');
        }

        $this->seo()->title('Associar a minha empresa');

        return view('business.claim', [
            'prefill' => (string) $request->query('empresa', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'company_name' => ['required_without:company_id', 'nullable', 'string', 'min:2', 'max:160'],
            'website' => ['nullable', 'url', 'max:190'],
            'work_email' => ['required', 'email:rfc', 'max:190'],
            'vat_number' => ['nullable', 'string', 'max:32'],
            'evidence' => ['nullable', 'string', 'max:2000'],
        ], [
            'company_name.required_without' => 'Indica o nome da empresa.',
            'work_email.required' => 'Indica o teu email profissional.',
        ]);

        $claim = DB::transaction(function () use ($request, $data): CompanyClaim {
            $company = isset($data['company_id'])
                ? Company::findOrFail($data['company_id'])
                : Company::firstOrCreate(
                    ['slug' => Company::generateSlug($data['company_name'])],
                    [
                        'name' => $data['company_name'],
                        'uuid' => (string) Str::uuid(),
                        'website' => $data['website'] ?? null,
                        'status' => CompanyStatus::Pending,
                        'country' => 'PT',
                        'created_by_user_id' => $request->user()->id,
                    ],
                );

            $claim = CompanyClaim::create([
                'company_id' => $company->id,
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'work_email' => mb_strtolower($data['work_email']),
                'vat_number' => $data['vat_number'] ?? null,
                'evidence' => $data['evidence'] ?? null,
            ]);

            // Sinal automático: email no mesmo domínio do site já conhecido
            // da empresa, e ficha ainda sem gestores. Continua a ser revisto,
            // mas entra no topo da fila com prioridade.
            if ($this->emailMatchesCompanyDomain($claim->work_email, $company)) {
                $claim->update(['evidence' => trim(($claim->evidence ?? '')."\n[auto] Email no domínio oficial da empresa.")]);
            }

            return $claim;
        });

        return redirect()->route('business.claim.pending')
            ->with('success', 'Pedido submetido. Validamos normalmente em 1 a 2 dias úteis.');
    }

    public function pending(Request $request): View|RedirectResponse
    {
        $claim = $this->pendingClaim($request);

        if ($claim === null) {
            return redirect()->route('business.claim.create');
        }

        $this->seo()->title('Pedido em análise');

        return view('business.claim-pending', ['claim' => $claim->load('company')]);
    }

    private function pendingClaim(Request $request): ?CompanyClaim
    {
        return CompanyClaim::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->latest()
            ->first();
    }

    private function emailMatchesCompanyDomain(string $email, Company $company): bool
    {
        if (blank($company->website)) {
            return false;
        }

        $companyHost = Str::of((string) parse_url($company->website, PHP_URL_HOST))->lower()->after('www.')->toString();
        $emailDomain = Str::of($email)->after('@')->lower()->toString();

        return $companyHost !== '' && $companyHost === $emailDomain;
    }
}
