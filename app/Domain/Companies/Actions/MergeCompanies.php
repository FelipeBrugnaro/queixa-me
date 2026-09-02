<?php

declare(strict_types=1);

namespace App\Domain\Companies\Actions;

use App\Domain\Companies\Enums\CompanyStatus;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Seo\Models\Redirect;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fusão de fichas duplicadas.
 *
 * Duplicados são inevitáveis quando os utilizadores podem propor empresas.
 * O que não pode acontecer é a fusão destruir histórico ou partir URLs:
 *
 *  - as reclamações passam para a ficha de destino, mantendo os seus próprios
 *    slugs (o URL de uma reclamação nunca muda);
 *  - o slug da ficha absorvida é guardado no histórico da ficha de destino e
 *    ganha um redirecionamento 301 permanente;
 *  - a ficha antiga fica em estado "merged" em vez de ser apagada, para que
 *    qualquer referência interna continue a resolver.
 */
class MergeCompanies
{
    public function handle(Company $source, Company $target): Company
    {
        if ($source->id === $target->id) {
            throw new RuntimeException('Não é possível fundir uma empresa consigo própria.');
        }

        if ($target->status === CompanyStatus::Merged) {
            throw new RuntimeException('A empresa de destino já foi fundida noutra ficha.');
        }

        return DB::transaction(function () use ($source, $target): Company {
            Complaint::withTrashed()->where('company_id', $source->id)
                ->update(['company_id' => $target->id]);

            Conversation::where('company_id', $source->id)
                ->update(['company_id' => $target->id]);

            // Gestores da ficha absorvida mantêm acesso à ficha resultante.
            foreach ($source->members()->get() as $member) {
                $target->members()->syncWithoutDetaching([
                    $member->id => ['role' => $member->pivot->role, 'revoked_at' => null],
                ]);
            }

            // Todo o histórico de slugs da origem passa a apontar ao destino.
            foreach ($source->slugs()->pluck('slug') as $slug) {
                $target->slugs()->firstOrCreate(['slug' => $slug], ['created_at' => now()]);
            }

            $target->slugs()->firstOrCreate(['slug' => $source->slug], ['created_at' => now()]);

            Redirect::updateOrCreate(
                ['from_path' => '/empresa/'.$source->slug],
                ['to_path' => '/empresa/'.$target->slug, 'status_code' => 301],
            );

            $source->forceFill([
                'status' => CompanyStatus::Merged,
                'merged_into_id' => $target->id,
                'is_indexable' => false,
            ])->save();

            $target->forceFill([
                'complaints_count' => Complaint::where('company_id', $target->id)->count(),
                'published_complaints_count' => Complaint::published()->where('company_id', $target->id)->count(),
            ])->save();

            return $target->refresh();
        });
    }
}
