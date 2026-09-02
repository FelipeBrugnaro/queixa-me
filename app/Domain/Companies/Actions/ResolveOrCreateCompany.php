<?php

declare(strict_types=1);

namespace App\Domain\Companies\Actions;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Enums\CompanyStatus;
use App\Domain\Companies\Models\Company;
use Illuminate\Support\Str;

/**
 * Resolve a empresa indicada no assistente de reclamação.
 *
 * PROBLEMA: deixar qualquer pessoa criar fichas de empresa livremente gera
 * duplicados em massa ("MEO", "Meo", "Meo Altice", "meo comunicações") e abre
 * a porta a fichas difamatórias com nomes inventados. Mas obrigar a escolher
 * de uma lista fechada impede reclamações sobre empresas ainda não listadas,
 * que são exatamente as que mais precisam de visibilidade.
 *
 * SOLUÇÃO: aceitamos o nome livre, mas a ficha nasce em "pending":
 *  - não é indexável nem aparece no diretório;
 *  - a reclamação segue o circuito normal de moderação;
 *  - o moderador aprova a ficha ou funde-a com a existente, e a fusão
 *    preserva os URLs através da tabela de slugs históricos.
 */
class ResolveOrCreateCompany
{
    public function handle(?int $companyId, ?string $rawName, ?string $website, User $author): ?Company
    {
        if ($companyId !== null) {
            $company = Company::find($companyId);

            if ($company?->isPublic()) {
                return $company;
            }
        }

        $rawName = trim((string) $rawName);

        if ($rawName === '') {
            return null;
        }

        // Antes de criar, tentar casar com uma ficha existente por nome
        // normalizado. Apanha a maioria dos duplicados óbvios sem intervenção.
        if ($existing = $this->findByNormalisedName($rawName)) {
            return $existing;
        }

        return Company::create([
            'name' => Str::limit($rawName, 160, ''),
            'slug' => Company::generateSlug($rawName),
            'website' => $this->normaliseWebsite($website),
            'status' => CompanyStatus::Pending,
            'country' => 'PT',
            'created_by_user_id' => $author->id,
        ]);
    }

    private function findByNormalisedName(string $name): ?Company
    {
        $slug = Str::slug($name);

        return Company::where('slug', $slug)
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
    }

    private function normaliseWebsite(?string $website): ?string
    {
        $website = trim((string) $website);

        if ($website === '') {
            return null;
        }

        if (! Str::startsWith($website, ['http://', 'https://'])) {
            $website = 'https://'.$website;
        }

        return filter_var($website, FILTER_VALIDATE_URL) ? $website : null;
    }
}
