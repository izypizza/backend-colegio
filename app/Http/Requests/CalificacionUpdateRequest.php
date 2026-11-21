<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CalificacionUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if ($this->filled('calificacion_literal')) {
            $this->merge(['calificacion_literal' => strtoupper(trim((string) $this->input('calificacion_literal')))]);
        }

        return [
            'nota' => 'sometimes|numeric|min:0|max:20',
            'calificacion_literal' => 'sometimes|nullable|string|in:AD,A,B,C',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nota.numeric' => 'La nota debe ser un número',
            'nota.min' => 'La nota mínima es 0',
            'nota.max' => 'La nota máxima es 20',
            'calificacion_literal.size' => 'El nivel de logro debe tener dos caracteres (AD, A, B o C)',
            'observaciones.max' => 'Las observaciones no pueden exceder 500 caracteres',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
