<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use App\Domain\Shared\Support\Countries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintContactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:190'],
            'postal_code' => ['nullable', 'string', 'max:16'],
            'locality' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', Rule::in(Countries::codes())],

            /*
             * Como assinar a reclamação.
             *
             * Não é uma caixa de "mostrar o meu nome" — o nome público aparece
             * sempre, a menos que a pessoa escolha o contrário. São duas
             * opções explícitas, para que a escolha seja consciente.
             *
             * Em qualquer dos casos a empresa recebe os dados de contacto:
             * o anonimato é perante o público, não perante quem tem de
             * resolver o problema.
             */
            'signature' => ['required', 'in:public,anonymous'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'nome próprio',
            'last_name' => 'apelido',
            'email' => 'email',
            'phone' => 'contacto telefónico',
            'postal_code' => 'código postal',
            'locality' => 'localidade',
            'district' => 'distrito',
            'country' => 'país',
            'signature' => 'forma de assinar',
        ];
    }

    public function messages(): array
    {
        return [
            'signature.required' => 'Escolhe como queres que a reclamação apareça no portal.',
        ];
    }

    /** A reclamação guarda um booleano; o formulário faz uma pergunta. */
    public function identityIsPublic(): bool
    {
        return $this->input('signature') === 'public';
    }
}
