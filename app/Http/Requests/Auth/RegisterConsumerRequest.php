<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\Accounts\Enums\Gender;
use App\Domain\Shared\Rules\AllowedPublicName;
use App\Domain\Shared\Rules\MinimumAge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterConsumerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'public_name' => [
                'required', 'string',
                'min:'.config('queixame.accounts.public_name_min'),
                'max:'.config('queixame.accounts.public_name_max'),
                Rule::unique('users', 'public_name'),
                new AllowedPublicName,
            ],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'birthdate' => ['required', 'date', 'before:today', new MinimumAge],
            'gender' => ['required', Rule::enum(Gender::class)],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:190', Rule::unique('users', 'email')],
            'password' => [
                'required', 'confirmed',
                Password::min(10)->letters()->numbers()->uncompromised(),
            ],
            // Consentimentos obrigatórios: sem eles não há base legal.
            'accept_terms' => ['accepted'],
            'accept_privacy' => ['accepted'],
            'accept_data_protection' => ['accepted'],
            // Opcional e separado: marketing nunca pode ser pré-aceite.
            'marketing_opt_in' => ['nullable', 'boolean'],
            // Campo isco anti-spam.
            'website' => ['nullable', 'size:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'public_name' => 'nome público',
            'first_name' => 'nome próprio',
            'last_name' => 'apelido',
            'birthdate' => 'data de nascimento',
            'gender' => 'género',
            'email' => 'endereço de email',
            'password' => 'palavra-passe',
        ];
    }

    public function messages(): array
    {
        return [
            'accept_terms.accepted' => 'Tens de aceitar os Termos e Condições.',
            'accept_privacy.accepted' => 'Tens de aceitar a Política de Privacidade.',
            'accept_data_protection.accepted' => 'Tens de aceitar a Política de Proteção de Dados.',
            'password.confirmed' => 'A confirmação da palavra-passe não coincide.',
            'website.size' => 'Pedido inválido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'public_name' => trim((string) $this->input('public_name')),
        ]);
    }
}
