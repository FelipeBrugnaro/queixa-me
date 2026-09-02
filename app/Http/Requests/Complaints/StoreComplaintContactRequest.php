<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use Illuminate\Foundation\Http\FormRequest;

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
            'country' => ['nullable', 'string', 'size:2'],

            // Se a identidade fica pública ou não é escolha do autor, e é
            // independente dos dados transmitidos à empresa: a empresa recebe
            // sempre os dados necessários para tratar o caso.
            'is_identity_public' => ['nullable', 'boolean'],
            'save_to_profile' => ['nullable', 'boolean'],
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
        ];
    }
}
