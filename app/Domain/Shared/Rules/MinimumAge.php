<?php

declare(strict_types=1);

namespace App\Domain\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

/**
 * Idade mínima para criar conta.
 *
 * O RGPD (art. 8) fixa em 16 anos, salvo lei nacional mais baixa, a idade a
 * partir da qual o próprio titular pode consentir o tratamento dos seus dados
 * em serviços da sociedade da informação. Portugal manteve os 16. Aceitar
 * registos abaixo disso tornaria o consentimento inválido e exporia menores
 * num contexto onde partilham dados de compras e moradas.
 */
class MinimumAge implements ValidationRule
{
    public function __construct(private readonly ?int $minimum = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $minimum = $this->minimum ?? (int) config('queixame.accounts.minimum_age');

        try {
            $birthdate = $value instanceof Carbon ? $value : Carbon::parse((string) $value);
        } catch (\Throwable) {
            $fail('A data de nascimento não é válida.');

            return;
        }

        if ($birthdate->isFuture()) {
            $fail('A data de nascimento não pode ser no futuro.');

            return;
        }

        if ($birthdate->diffInYears(Carbon::now()) < $minimum) {
            $fail("Tens de ter pelo menos {$minimum} anos para criares conta no queixa.me.");
        }
    }
}
