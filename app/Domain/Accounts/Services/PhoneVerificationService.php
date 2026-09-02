<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Services;

use App\Domain\Accounts\Models\PhoneVerification;
use App\Domain\Accounts\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Confirmação de telefone por SMS.
 *
 * ESTADO: arquitetura completa, envio simulado.
 *
 * Todo o fluxo funciona hoje — geração de código, hash, expiração, limite de
 * tentativas, marcação do número como confirmado. O único ponto por ligar é
 * o transporte: dispatch() escreve no log em vez de chamar um fornecedor.
 * Integrar um serviço real significa implementar um driver e trocar a linha
 * do log; nada mais no domínio precisa de mudar.
 */
class PhoneVerificationService
{
    private const MAX_ATTEMPTS = 5;

    public function request(User $user, string $phone): PhoneVerification
    {
        $phone = $this->normalise($phone);

        if (User::where('phone', $phone)->whereKeyNot($user->id)->whereNotNull('phone_verified_at')->exists()) {
            throw new RuntimeException('Este número já está confirmado noutra conta.');
        }

        PhoneVerification::where('user_id', $user->id)->whereNull('verified_at')->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $verification = PhoneVerification::create([
            'user_id' => $user->id,
            'phone' => $phone,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addMinutes((int) config('queixame.accounts.phone_code_ttl_minutes')),
        ]);

        $this->dispatch($phone, $code);

        return $verification;
    }

    public function confirm(User $user, string $code): void
    {
        $verification = PhoneVerification::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if ($verification === null || $verification->expires_at->isPast()) {
            throw new RuntimeException('O código expirou. Pede um novo.');
        }

        if ($verification->attempts >= self::MAX_ATTEMPTS) {
            throw new RuntimeException('Demasiadas tentativas. Pede um novo código.');
        }

        $verification->increment('attempts');

        if (! hash_equals($verification->code_hash, hash('sha256', trim($code)))) {
            throw new RuntimeException('Código incorreto.');
        }

        $verification->forceFill(['verified_at' => now()])->save();

        $user->forceFill([
            'phone' => $verification->phone,
            'phone_verified_at' => now(),
        ])->save();
    }

    /** Ponto único de integração com um fornecedor de SMS. */
    private function dispatch(string $phone, string $code): void
    {
        $driver = (string) config('services.sms.driver', 'log');

        if ($driver === 'log') {
            Log::info('SMS de verificação (simulado)', [
                'to' => $phone,
                'message' => "O teu código de confirmação queixa.me é {$code}.",
            ]);

            return;
        }

        throw new RuntimeException('Envio de SMS ainda não está disponível.');
    }

    private function normalise(string $phone): string
    {
        $digits = preg_replace('/[^\d+]/', '', $phone) ?? '';

        if (! str_starts_with($digits, '+') && strlen($digits) === 9) {
            $digits = '+351'.$digits;
        }

        return $digits;
    }
}
