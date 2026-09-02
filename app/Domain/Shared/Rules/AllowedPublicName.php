<?php

declare(strict_types=1);

namespace App\Domain\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * Nome público válido.
 *
 * O nome público é a identidade visível nas páginas indexadas, por isso não
 * pode parecer-se com o portal nem com uma entidade oficial: alguém chamado
 * "moderacao_queixame" poderia induzir em erro noutras reclamações.
 */
class AllowedPublicName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = trim((string) $value);
        $normalised = Str::lower(Str::ascii($value));

        if (! preg_match('/^[\p{L}\p{N}][\p{L}\p{N} ._-]*$/u', $value)) {
            $fail('O nome público só pode conter letras, números, espaços, pontos, hífens e sublinhados.');

            return;
        }

        foreach ((array) config('queixame.accounts.reserved_public_names') as $reserved) {
            if (str_contains($normalised, Str::lower($reserved))) {
                $fail('Este nome público não está disponível.');

                return;
            }
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail('Não uses um endereço de email como nome público — ficaria visível a toda a gente.');
        }
    }
}
