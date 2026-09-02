<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Actions;

use App\Domain\Accounts\Enums\UserStatus;
use App\Domain\Accounts\Models\AuditLog;
use App\Domain\Accounts\Models\User;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Models\ComplaintContactDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Execução do direito ao apagamento por anonimização.
 *
 * Ver a nota completa em PrivacyController. Em resumo: apagamos tudo o que
 * identifica a pessoa e mantemos o conteúdo público que já cumpriu a sua
 * função informativa — a reclamação e a resposta da empresa passam a existir
 * sem titular.
 *
 * O que é destruído: nome, apelido, email, telefone, data de nascimento,
 * género, morada, avatar, contas sociais, dados de contacto cifrados de
 * todas as reclamações e os IPs de submissão.
 *
 * O que permanece: o texto das reclamações publicadas, as respostas das
 * empresas, as avaliações e os indicadores agregados.
 */
class AnonymiseUser
{
    public function handle(User $user, ?User $performedBy = null): User
    {
        return DB::transaction(function () use ($user, $performedBy): User {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->socialAccounts()->delete();
            $user->emailChangeRequests()->delete();

            ComplaintContactDetail::whereIn(
                'complaint_id',
                Complaint::where('user_id', $user->id)->select('id')
            )->each(fn (ComplaintContactDetail $details) => $details->purge());

            Complaint::where('user_id', $user->id)->update([
                'submitted_ip' => null,
                'is_identity_public' => false,
            ]);

            $placeholder = 'removido-'.Str::lower(Str::random(12));

            $user->forceFill([
                'status' => UserStatus::Anonymised,
                'public_name' => null,
                'name' => 'Utilizador removido',
                'first_name' => null,
                'last_name' => null,
                'birthdate' => null,
                'gender' => null,
                'email' => $placeholder.'@anonimizado.queixa.me',
                'email_verified_at' => null,
                'password' => null,
                'phone' => null,
                'phone_verified_at' => null,
                'country' => null,
                'district' => null,
                'locality' => null,
                'avatar_path' => null,
                'marketing_opt_in' => false,
                'remember_token' => null,
                'last_login_ip' => null,
                'anonymised_at' => now(),
            ])->save();

            $user->delete();

            AuditLog::create([
                'user_id' => $performedBy?->id,
                'action' => 'user.anonymised',
                'auditable_type' => $user->getMorphClass(),
                'auditable_id' => $user->id,
                'properties' => ['uuid' => $user->uuid],
                'created_at' => now(),
            ]);

            return $user;
        });
    }
}
