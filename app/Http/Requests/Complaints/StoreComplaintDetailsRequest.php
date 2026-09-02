<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Terceiro passo do assistente.
 *
 * Pedimos deliberadamente pouco. Número de encomenda e valor envolvido foram
 * retirados: são dados que a maioria das pessoas não tem à mão no momento em
 * que escreve, e cada campo extra neste ponto do formulário custa
 * submissões. O que for preciso para identificar o processo é tratado
 * depois, no canal privado com a empresa.
 */
class StoreComplaintDetailsRequest extends FormRequest
{
    public function rules(): array
    {
        $attachments = (array) config('queixame.complaints.attachments');

        return [
            'title' => [
                'required', 'string',
                'min:'.config('queixame.complaints.title_min'),
                'max:'.config('queixame.complaints.title_max'),
            ],
            'category_id' => ['nullable', 'integer', 'exists:company_categories,id'],
            'occurred_on' => ['nullable', 'date', 'before_or_equal:today', 'after:'.now()->subYears(5)->toDateString()],
            'desired_resolution' => ['nullable', 'string', 'max:'.config('queixame.complaints.desired_resolution_max')],

            'attachments' => ['nullable', 'array', 'max:'.$attachments['max_files']],
            'attachments.*' => [
                'file',
                'max:'.$attachments['max_size_kb'],
                'mimes:'.implode(',', $attachments['allowed_extensions']),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'assunto',
            'occurred_on' => 'data da ocorrência',
            'desired_resolution' => 'resolução pretendida',
            'attachments' => 'anexos',
        ];
    }

    public function messages(): array
    {
        return [
            'occurred_on.after' => 'Só aceitamos reclamações sobre ocorrências dos últimos 5 anos.',
            'attachments.*.mimes' => 'Só aceitamos imagens e ficheiros PDF.',
            'attachments.*.max' => 'Cada ficheiro tem de ter menos de :max KB.',
        ];
    }
}
