<?php

namespace App\Http\Requests\API;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Support\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class FormRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                'VALIDATION_ERROR',
                'Validation failed.',
                $validator->errors()->toArray(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            )
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->trimStringInputs($this->all()));
    }

    private function trimStringInputs(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->trimStringInputs($value);
            }
        }

        return $data;
    }
}
