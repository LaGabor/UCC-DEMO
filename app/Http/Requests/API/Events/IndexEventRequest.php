<?php

namespace App\Http\Requests\API\Events;

use App\Http\Requests\API\FormRequest;
use Illuminate\Validation\Rule;

class IndexEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['title', 'description', 'occurs_at', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $sortBy = (string) $this->input('sort_by', 'occurs_at');
        $sortDir = strtolower((string) $this->input('sort_dir', 'asc'));

        $this->merge([
            'sort_by' => $sortBy !== '' ? $sortBy : 'occurs_at',
            'sort_dir' => in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc',
            'per_page' => (int) $this->input('per_page', 10),
        ]);
    }
}

