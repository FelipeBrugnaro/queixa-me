<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Services;

use App\Domain\Accounts\Models\EmailChangeRequest;
use App\Domain\Accounts\Models\User;
use App\Domain\Accounts\Notifications\ConfirmEmailChange;
use App\Domain\Accounts\Notifications\EmailChangeRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Alteração de email em duas fases.
 *
 * PROBLEMA: alterar o email diretamente transforma o formulário de perfil
 * num mecanismo de tomada de conta — basta o acesso momentâneo a uma sessão
 * aberta para trocar o email e depois recuperar a palavra-passe.
 *
 * SOLUÇÃO:
 *  1. O endereço atual continua ativo até à confirmação.
 *  2. O token é enviado para o NOVO endereço e guardado com hash: quem tiver
 *     acesso à base de dados não consegue usá-lo.
 *  3. O endereço ANTIGO recebe um aviso com a possibilidade de cancelar —
 *     é isto que dá ao titular real a hipótese de travar um ataque.
 *  4. Unicidade verificada no pedido e outra vez na confirmação, dentro de
 *     transação, para fechar a janela de concorrência entre dois pedidos.
 */
class EmailChangeService
{
    public function request(User $user, string $newEmail, ?string $ip = null): EmailChangeRequest
    {
        $newEmail = mb_strtolower(trim($newEmail));

        if ($newEmail === mb_strtolower($user->email)) {
            throw new RuntimeException('Este já é o teu endereço de email atual.');
        }

        if (User::where('email', $newEmail)->whereKeyNot($user->id)->exists()) {
            throw new RuntimeException('Já existe uma conta associada a esse endereço de email.');
        }

        return DB::transaction(function () use ($user, $newEmail, $ip): EmailChangeRequest {
            // Um pedido de cada vez: pedidos anteriores ficam sem efeito.
            EmailChangeRequest::where('user_id', $user->id)
                ->whereNull('confirmed_at')
                ->whereNull('cancelled_at')
                ->update(['cancelled_at' => now()]);

            $token = Str::random(64);

            $request = EmailChangeRequest::create([
                'user_id' => $user->id,
                'new_email' => $newEmail,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes((int) config('queixame.accounts.email_change_ttl_minutes')),
                'requested_ip' => $ip,
            ]);

            Notification::route('mail', $newEmail)
                ->notify(new ConfirmEmailChange($user, $token, $request->expires_at));

            $user->notify(new EmailChangeRequested($newEmail));

            return $request;
        });
    }

    public function confirm(string $token): User
    {
        $hash = hash('sha256', $token);

        return DB::transaction(function () use ($hash): User {
            $request = EmailChangeRequest::where('token_hash', $hash)
                ->whereNull('confirmed_at')
                ->whereNull('cancelled_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($request === null) {
                throw new RuntimeException('O link de confirmação é inválido ou expirou.');
            }

            $user = $request->user;

            if (User::where('email', $request->new_email)->whereKeyNot($user->id)->exists()) {
                $request->forceFill(['cancelled_at' => now()])->save();

                throw new RuntimeException('Entretanto esse endereço passou a estar associado a outra conta.');
            }

            $user->forceFill([
                'email' => $request->new_email,
                // O novo endereço fica verificado por ter sido ele a receber
                // e usar o token de confirmação.
                'email_verified_at' => now(),
            ])->save();

            $request->forceFill(['confirmed_at' => now()])->save();

            return $user;
        });
    }

    public function cancel(User $user): void
    {
        EmailChangeRequest::where('user_id', $user->id)
            ->whereNull('confirmed_at')
            ->whereNull('cancelled_at')
            ->update(['cancelled_at' => now()]);
    }

    public function pendingFor(User $user): ?EmailChangeRequest
    {
        return EmailChangeRequest::where('user_id', $user->id)
            ->whereNull('confirmed_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }
}
