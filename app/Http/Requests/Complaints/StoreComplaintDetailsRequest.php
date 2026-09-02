<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use Illuminate\Foundation\Http\FormRequest;

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
            'extra_info' => ['nullable', 'string', 'max:'.config('queixame.complaints.extra_info_max')],
            'purchase_reference' => ['nullable', 'string', 'max:120'],
            'amount_involved' => ['nullable', 'numeric', 'min:0', 'max:9999999'],

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
            'extra_info' => 'informações adicionais',
            'purchase_reference' => 'referência da compra',
            'amount_involved' => 'valor envolvido',
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

    protected function prepareForValidation(): void
    {
        if ($this->filled('amount_involved')) {
            $this->merge([
                'amount_involved' => str_replace(',', '.', (string) $this->input('amount_involved')),
            ]);
        }
    }
}
