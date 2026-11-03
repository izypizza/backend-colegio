<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AsistenciaUpdateRequest extends FormRequest
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
        return [
            'estudiante_id' => 'sometimes|exists:estudiantes,id',
            'materia_id' => 'sometimes|exists:materias,id',
            'fecha' => 'sometimes|date|before_or_equal:today',
            'estado' => 'sometimes|in:presente,ausente,tarde',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'estudiante_id.exists' => 'El estudiante no existe',
            'materia_id.exists' => 'La materia no existe',
            'fecha.date' => 'La fecha debe ser válida',
            'fecha.before_or_equal' => 'No se puede registrar asistencia para una fecha futura',
            'estado.in' => 'El estado debe ser: presente, ausente o tarde',
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
