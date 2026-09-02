<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintDescriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => [
                'required', 'string',
                'min:'.config('queixame.complaints.description_min'),
                'max:'.config('queixame.complaints.description_max'),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['description' => 'descrição'];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Descreve o que aconteceu com pelo menos :min caracteres. Quanto mais concreto fores, mais facilmente a empresa consegue resolver.',
        ];
    }
}
